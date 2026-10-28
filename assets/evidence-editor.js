(() => {
    const dialog = document.querySelector('#evidence-editor');
    const canvas = document.querySelector('#evidence-canvas');
    const hint = document.querySelector('#editor-hint');
    const cropShape = document.querySelector('#editor-crop-shape');
    const editForm = document.querySelector('#edited-evidence-form');
    if (!dialog || !canvas || !editForm) return;

    const context = canvas.getContext('2d');
    let original = null;
    let working = null;
    let mode = 'none';
    let selection = null;
    let drawing = false;
    let lastPoint = null;
    let outputMime = 'image/jpeg';
    let activeEvidenceId = '';
    let activeReviewId = '';

    const copyCanvas = source => {
        const copy = document.createElement('canvas');
        copy.width = source.width;
        copy.height = source.height;
        copy.getContext('2d').drawImage(source, 0, 0);
        return copy;
    };

    const point = event => {
        const rect = canvas.getBoundingClientRect();
        return {
            x: Math.max(0, Math.min(canvas.width, event.clientX - rect.left)),
            y: Math.max(0, Math.min(canvas.height, event.clientY - rect.top))
        };
    };

    const redraw = () => {
        const scale = Math.min(1, 900 / working.width, 600 / working.height);
        const displayWidth = Math.max(1, Math.round(working.width * scale));
        const displayHeight = Math.max(1, Math.round(working.height * scale));
        if (canvas.width !== displayWidth || canvas.height !== displayHeight) {
            canvas.width = displayWidth;
            canvas.height = displayHeight;
        }
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.drawImage(working, 0, 0, canvas.width, canvas.height);
        if (selection) {
            context.save();
            context.fillStyle = 'rgba(23, 105, 170, .18)';
            context.strokeStyle = '#1769aa';
            context.lineWidth = 2;
            if (selection.type === 'freehand') {
                context.setLineDash([7, 5]);
                context.beginPath();
                selection.points.forEach((current, index) => {
                    if (index === 0) context.moveTo(current.x, current.y);
                    else context.lineTo(current.x, current.y);
                });
                context.closePath();
                context.stroke();
            } else {
                context.setLineDash([]);
                context.fillRect(selection.x, selection.y, selection.width, selection.height);
                context.strokeRect(selection.x, selection.y, selection.width, selection.height);
            }
            context.restore();
        }
    };

    const setMode = next => {
        mode = next;
        canvas.classList.toggle('is-crop-mode', mode === 'crop');
        canvas.classList.toggle('is-annotate-mode', mode === 'annotate');
        hint.textContent = mode === 'crop'
            ? (selection ? 'Release the mouse to use the shape you drew.' : cropShape.value === 'rectangle' ? 'Drag a rectangle around the area to keep.' : 'Draw the shape to keep, then release.')
            : mode === 'annotate'
                ? 'Draw directly on the image. Use Reset to remove all edits.'
                : 'Choose Annotate to draw on the image, or Crop to draw a crop shape.';
    };

    const rotate = degrees => {
        const next = document.createElement('canvas');
        next.width = working.height;
        next.height = working.width;
        const nextContext = next.getContext('2d');
        nextContext.translate(next.width / 2, next.height / 2);
        nextContext.rotate(degrees * Math.PI / 180);
        nextContext.drawImage(working, -working.width / 2, -working.height / 2);
        working = next;
        outputMime = 'image/jpeg';
        selection = null;
        redraw();
    };

    const cropRectangle = () => {
        if (!selection || selection.width < 8 || selection.height < 8) return;
        const scaleX = working.width / canvas.width;
        const scaleY = working.height / canvas.height;
        const next = document.createElement('canvas');
        next.width = Math.max(1, Math.round(selection.width * scaleX));
        next.height = Math.max(1, Math.round(selection.height * scaleY));
        next.getContext('2d').drawImage(
            working,
            Math.round(selection.x * scaleX), Math.round(selection.y * scaleY), next.width, next.height,
            0, 0, next.width, next.height
        );
        working = next;
        selection = null;
        setMode('none');
        cropShape.hidden = true;
        redraw();
    };

    const cropFreehand = () => {
        if (!selection || selection.points.length < 3) return;
        const scaleX = working.width / canvas.width;
        const scaleY = working.height / canvas.height;
        const points = selection.points.map(({x, y}) => ({x: x * scaleX, y: y * scaleY}));
        const minX = Math.max(0, Math.floor(Math.min(...points.map(({x}) => x))));
        const minY = Math.max(0, Math.floor(Math.min(...points.map(({y}) => y))));
        const maxX = Math.min(working.width, Math.ceil(Math.max(...points.map(({x}) => x))));
        const maxY = Math.min(working.height, Math.ceil(Math.max(...points.map(({y}) => y))));
        const width = maxX - minX;
        const height = maxY - minY;
        if (width < 8 || height < 8) return;

        const next = document.createElement('canvas');
        next.width = width;
        next.height = height;
        const nextContext = next.getContext('2d');
        nextContext.save();
        nextContext.beginPath();
        points.forEach((current, index) => {
            const x = current.x - minX;
            const y = current.y - minY;
            if (index === 0) nextContext.moveTo(x, y);
            else nextContext.lineTo(x, y);
        });
        nextContext.closePath();
        nextContext.clip();
        nextContext.drawImage(working, minX, minY, width, height, 0, 0, width, height);
        nextContext.restore();
        working = next;
        outputMime = 'image/png';
        selection = null;
        setMode('none');
        cropShape.hidden = true;
        redraw();
    };

    const openEditor = card => {
        const image = new Image();
        image.onload = () => {
            original = document.createElement('canvas');
            original.width = image.naturalWidth;
            original.height = image.naturalHeight;
            original.getContext('2d').drawImage(image, 0, 0);
            working = copyCanvas(original);
            outputMime = 'image/jpeg';
            selection = null;
            activeEvidenceId = card.dataset.evidenceId;
            activeReviewId = card.dataset.reviewId;
            setMode('none');
            redraw();
            dialog.showModal();
        };
        image.src = card.dataset.imageUrl;
    };

    document.querySelectorAll('[data-edit-evidence]').forEach(button => {
        button.addEventListener('click', () => openEditor(button.closest('[data-evidence-editor]')));
    });

    document.querySelectorAll('[data-editor-rotate]').forEach(button => {
        button.addEventListener('click', () => rotate(Number(button.dataset.editorRotate)));
    });
    document.querySelector('#editor-crop').addEventListener('click', () => {
        if (mode === 'crop') {
            setMode('none');
            cropShape.hidden = true;
            selection = null;
            redraw();
            return;
        }
        cropShape.hidden = false;
        cropShape.focus();
        setMode('crop');
    });
    cropShape.addEventListener('change', () => {
        selection = null;
        setMode('crop');
        redraw();
    });
    document.querySelector('#editor-annotate').addEventListener('click', () => setMode(mode === 'annotate' ? 'none' : 'annotate'));
    document.querySelector('#editor-reset').addEventListener('click', () => {
        working = copyCanvas(original);
        selection = null;
        setMode('none');
        cropShape.hidden = true;
        redraw();
    });
    document.querySelector('#editor-cancel').addEventListener('click', () => dialog.close());
    document.querySelector('#editor-save').addEventListener('click', () => {
        document.querySelector('#edited-evidence-id').value = activeEvidenceId;
        document.querySelector('#edited-review-id').value = activeReviewId;
        document.querySelector('#edited-image').value = working.toDataURL(outputMime, .92);
        editForm.submit();
    });

    canvas.addEventListener('pointerdown', event => {
        if (mode === 'none') return;
        canvas.setPointerCapture(event.pointerId);
        const current = point(event);
        if (mode === 'crop') {
            selection = cropShape.value === 'rectangle'
                ? { type: 'rectangle', x: current.x, y: current.y, width: 0, height: 0 }
                : { type: 'freehand', points: [current] };
        } else {
            drawing = true;
            lastPoint = current;
        }
    });

    canvas.addEventListener('pointermove', event => {
        if (mode === 'crop' && selection) {
            const current = point(event);
            if (selection.type === 'freehand') {
                const previous = selection.points[selection.points.length - 1];
                if (Math.hypot(current.x - previous.x, current.y - previous.y) >= 2) {
                    selection.points.push(current);
                }
                redraw();
                return;
            }
            const width = current.x - selection.x;
            const height = current.y - selection.y;
            selection.width = width;
            selection.height = height;
            redraw();
            return;
        }
        if (mode !== 'annotate' || !drawing) return;
        const current = point(event);
        const scaleX = working.width / canvas.width;
        const scaleY = working.height / canvas.height;
        const drawContext = working.getContext('2d');
        drawContext.strokeStyle = '#e21d2f';
        drawContext.lineWidth = Math.max(4, 4 * Math.max(scaleX, scaleY));
        drawContext.lineCap = 'round';
        drawContext.beginPath();
        drawContext.moveTo(lastPoint.x * scaleX, lastPoint.y * scaleY);
        drawContext.lineTo(current.x * scaleX, current.y * scaleY);
        drawContext.stroke();
        lastPoint = current;
        redraw();
    });

    canvas.addEventListener('pointerup', event => {
        drawing = false;
        lastPoint = null;
        if (selection?.type === 'freehand') {
            const finalPoint = point(event);
            const previous = selection.points[selection.points.length - 1];
            if (!previous || Math.hypot(finalPoint.x - previous.x, finalPoint.y - previous.y) >= 2) {
                selection.points.push(finalPoint);
            }
            if (selection.points.length >= 3) cropFreehand();
        } else if (selection) {
            selection = {
                type: 'rectangle',
                x: Math.min(selection.x, selection.x + selection.width),
                y: Math.min(selection.y, selection.y + selection.height),
                width: Math.abs(selection.width),
                height: Math.abs(selection.height)
            };
            if (mode === 'crop') cropRectangle();
            else redraw();
        }
    });
})();
