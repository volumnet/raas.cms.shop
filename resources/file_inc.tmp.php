<?php
namespace RAAS\CMS\Shop;

$getFileIcon = function($row)
{
    $ext = pathinfo($row->fileURL, PATHINFO_EXTENSION);
    switch ($ext) {
        case 'zip': case 'rar': case 'tar': case 'gz': case '7z':
            $icon = 'archive';
            break;
        case 'mp3': case 'wav': case 'ogg': case 'mid': case 'rmi':
            $icon = 'audio';
            break;
        case 'php': case 'html': case 'cs': case 'class': case 'inc': case 'js': case 'css':
            $icon = 'code';
            break;
        case 'jpg': case 'gif': case 'jpeg': case 'pjpeg': case 'bmp': case 'tif': case 'tiff':
            $icon = 'picture';
            break;
        case 'pdf':
            $icon = 'pdf';
            break;
        case 'ppt': case 'pptx':
            $icon = 'powerpoint';
            break;
        case 'avi': case 'mpg': case 'flv':
            $icon = 'video';
            break;
        case 'doc': case 'docx': case 'rft':
            $icon = 'word';
            break;
        default:
            $icon = 'text';
            break;
    }
    return 'fa-file-' . htmlspecialchars($icon) . '-o';
};
