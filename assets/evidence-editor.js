(() => {
    const dialog = document.querySelector('#evidence-editor');
    const canvas = document.querySelector('#evidence-canvas');
    const hint = document.querySelector('#editor-hint');
    const editForm = document.querySelector('#edited-evidence-form');
    if (!dialog || !canvas || !editForm) return;

    const context = canvas.getContext('2d');
    let original = null;
    let working = null;
    let mode = 'none';
    let selection = null;
    let drawing = false;
    let lastPoint = null;
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
        canvas.width = Math.max(1, Math.round(working.width * scale));
        canvas.height = Math.max(1, Math.round(working.height * scale));
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.drawImage(working, 0, 0, canvas.width, canvas.height);
        if (selection) {
            context.save();
            context.fillStyle = 'rgba(23, 105, 170, .18)';
            context.strokeStyle = '#1769aa';
            context.lineWidth = 2;
            context.fillRect(selection.x, selection.y, selection.width, selection.height);
            context.strokeRect(selection.x, selection.y, selection.width, selection.height);
            context.restore();
        }
    };

    const setMode = next => {
        mode = next;
        canvas.classList.toggle('is-crop-mode', mode === 'crop');
        canvas.classList.toggle('is-annotate-mode', mode === 'annotate');
        hint.textContent = mode === 'crop'
            ? (selection ? 'Release the mouse to finish the crop selection, then choose Crop again to apply it.' : 'Drag across the image to select the area to keep.')
            : mode === 'annotate'
                ? 'Draw directly on the image. Use Reset to remove all edits.'
                : 'Choose Annotate to draw on the image, or Crop to select an area.';
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
        selection = null;
        redraw();
    };

    const crop = () => {
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
        if (mode === 'crop' && selection) crop();
        else setMode('crop');
    });
    document.querySelector('#editor-annotate').addEventListener('click', () => setMode(mode === 'annotate' ? 'none' : 'annotate'));
    document.querySelector('#editor-reset').addEventListener('click', () => {
        working = copyCanvas(original);
        selection = null;
        setMode('none');
        redraw();
    });
    document.querySelector('#editor-cancel').addEventListener('click', () => dialog.close());
    document.querySelector('#editor-save').addEventListener('click', () => {
        document.querySelector('#edited-evidence-id').value = activeEvidenceId;
        document.querySelector('#edited-review-id').value = activeReviewId;
        document.querySelector('#edited-image').value = working.toDataURL('image/jpeg', .92);
        editForm.submit();
    });

    canvas.addEventListener('pointerdown', event => {
        if (mode === 'none') return;
        canvas.setPointerCapture(event.pointerId);
        const current = point(event);
        if (mode === 'crop') {
            selection = { x: current.x, y: current.y, width: 0, height: 0 };
        } else {
            drawing = true;
            lastPoint = current;
        }
    });

    canvas.addEventListener('pointermove', event => {
        if (mode === 'crop' && selection) {
            const current = point(event);
            selection.width = current.x - selection.x;
            selection.height = current.y - selection.y;
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

    canvas.addEventListener('pointerup', () => {
        drawing = false;
        lastPoint = null;
        if (selection) {
            selection = {
                x: Math.min(selection.x, selection.x + selection.width),
                y: Math.min(selection.y, selection.y + selection.height),
                width: Math.abs(selection.width),
                height: Math.abs(selection.height)
            };
            redraw();
        }
    });
})();
