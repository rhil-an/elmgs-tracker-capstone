<?php
declare(strict_types=1);
/** Evidence links always use the authenticated endpoint, never public paths. */
function review_attachments_html(array $files): string {
    $escape=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');
    if (!$files) return '<span class="evidence-unavailable">No evidence submitted</span>';
    $html='<div class="review-attachments">';
    foreach ($files as $file) {
        $name=$escape($file['original_name']??'Evidence');
        $mime=$file['mime_type']??'';
        $bytes=base64_decode($file['content_base64']??'',true);
        $image=in_array($mime,['image/jpeg','image/png'],true);
        $dimensions=$image && is_string($bytes) ? @getimagesizefromstring($bytes) : false;
        $valid=$bytes!==false && $bytes!=='' && ($image?($dimensions!==false && $dimensions['mime']===$mime):$mime==='application/pdf') && !empty($file['id']);
        $html.='<div class="review-attachment"><span class="evidence-filename">'.$name.'</span>';
        if (!$valid) { $html.='<span class="evidence-unavailable">Evidence unavailable</span></div>'; continue; }
        $url='evidence.php?id='.(int)$file['id'];
        $html.='<a href="'.$url.'&amp;preview=1" target="_blank" rel="noopener" aria-label="View '.$name.'">';
        $html.=$image?'<img class="review-stamp" src="'.$url.'&amp;preview=1" alt="Submitted evidence: '.$name.'" loading="lazy">':'<span class="review-document" role="img" aria-label="PDF document">PDF document</span>';
        $html.='</a><span class="evidence-actions"><a href="'.$url.'&amp;preview=1" target="_blank" rel="noopener">View full '.($image?'image':'PDF').'</a><a href="'.$url.'">Download</a></span></div>';
    }
    return $html.'</div>';
}
