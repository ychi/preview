<?php
if( file_exists('preview') ) {
    header('location: preview/');
}
else {
    header('location: preview.php');
}
