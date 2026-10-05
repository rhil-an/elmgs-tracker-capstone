<?php
declare(strict_types=1);
require __DIR__.'/app/submissions.php';
$user=require_user();
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$file=demo_state()["evidence"][$id?:0]??null;
if (!$file || ($user['role']!=='admin' && $user['student_id']!==$file['student_id'])) { http_response_code(404); exit('Evidence not found.'); }
$content=base64_decode($file['content_base64'],true);
if ($content===false) throw new RuntimeException('Evidence is damaged.');
header('Content-Type: '.$file['mime_type']);
$disposition=($_GET['preview']??'')==='1' ? 'inline' : 'attachment';
header('Content-Disposition: '.$disposition.'; filename="evidence-'.$file['id'].'.'.match($file['mime_type']){'image/jpeg'=>'jpg','image/png'=>'png',default=>'pdf'}.'"');
header('Content-Length: '.strlen($content));
header("Content-Security-Policy: sandbox; default-src 'none'");
echo $content;
