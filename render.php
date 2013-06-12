#!/usr/bin/env php
<?php
/* author: pedro */
require "vendor/autoload.php";

use Corneltek\PreviewSystem\TemplateDirs;
use Corneltek\PreviewSystem\VirtualPath;
use Corneltek\PreviewSystem\MySplFileInfo;
use Symfony\Component\Yaml\Yaml;

function copyr($source, $dest) 
{
    if ( ! file_exists($dest) ) {
        mkdir($dest, 0755, true);
    }

    foreach (
        $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST) as $item
    ) 
    {
        if ($item->isDir()) {
            mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
        } else {
            copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
        }
    }
}

$verbose = false;
$template_dir = 'design';
$preview_dir = 'preview';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($template_dir),
                                                      RecursiveIteratorIterator::CHILD_FIRST);


// for windows, we don't need chmod.
if ( PHP_OS !== 'Windows' && PHP_OS !== 'WINNT' ) {
    echo "Chmoding to +rw...\n";
    system("chmod -R a+rw $template_dir");
}

if ( ! file_exists($preview_dir) ) {
    mkdir($preview_dir);
}

echo "Cleaning preview directory...\n";

futil_rmtree($preview_dir);

echo "Running scss to compile scss files...\n";
echo "sass: " , system("which sass");
putenv('LANG=en_US.UTF-8');
putenv('LC_ALL=en_US.UTF-8');
system("sass --compass --update --force $template_dir/static/scss:$template_dir/static/css");
# system("scss --compass --update --force $template_dir/static/css/lib");
# system("ruby scripts/cssmin");

echo "Copying design files to preview directory..\n";
copyr($template_dir, $preview_dir);


if ( PHP_OS !== 'Windows' && PHP_OS !== 'WINNT' ) {
    system("chmod -R og+rw $preview_dir/");
}

echo "Starting Rendering...\n";
foreach ($iterator as $sourceFile) {

    # echo $sourceFile->getPathname() . "\n";
    if( preg_match('/^\.\.?/' ,$sourceFile->getFilename() ) )
        continue;


    $filename = $sourceFile->getFilename();

    $template_path = $sourceFile->getPath();
    if ($sourceFile->isFile()) {
        $extension = futil_get_extension( $sourceFile->getFilename() );

        /* looking for twig or html files */
        // if( preg_match('/(html?|twig)$/', $sourceFile->getExtension() ) )   // XXX: only for php5.3.8
        if( preg_match('/(html?|twig)$/', $extension ) )
        {
            $dataFilePath = futil_replace_extension($sourceFile->getPathname(),'yml');
            if ( file_exists($dataFilePath) ) {
                $data = Yaml::parse($dataFilePath);
            }


            $dirs =  array( $sourceFile->getPath() , $template_dir );

            $loader = new Twig_Loader_Filesystem( $dirs );
            $twig = new Twig_Environment($loader, array() );

            if( class_exists('Twig_Extension_Markdown',true) ) {
                $twig->addExtension( new \Twig_Extension_Markdown );
            }

            $template = $twig->loadTemplate( $sourceFile->getFilename() );
            $content = $template->render(array(   ));


            // filter out java i18n tag
            // $content = preg_replace('#{(?<TAG>\w+).*?}(.*?){/\k<TAG>}#', '$2', $content );

            // generic path filter
            $content = preg_replace('#(?<=href=")/#','', $content);
            $content = preg_replace('#(?<=src=")/#','', $content);

            $template_filepath = $sourceFile->getPathname();

            $new_filename = preg_replace( '/\.twig$/', '', $template_filepath );
            $preview_filepath = $preview_dir . DIRECTORY_SEPARATOR . substr( $new_filename , strlen( $template_dir ) + 1 );

            /* put rendered content into preview dir */
            if( $verbose )  {
                echo "Rendering $template_filepath => $preview_filepath...\n";
                # print_r( $dirs );
            } else {
                echo ".";
            }
            if ( false === file_put_contents( $preview_filepath, $content ) ) {
                die("Error: can not render file.");
            }
        }
    }
}

$fp = opendir($preview_dir);
$links = array();
$langs = array( 
    'en' => '英文', 
    'en_US' => '英文', 
    'zh_CN' => '簡體中文', 
    'zh_TW' => '繁體中文',
);
while( $file = readdir($fp) ) {
    // $info = new SplFileInfo($file);
    if( isset($langs[$file]) ) {
        $links[$file] = $langs[$file];
    }
}
closedir($fp);
if( count($links) 
    && ! file_exists( $preview_dir . DIRECTORY_SEPARATOR . 'index.html' ) ) 
{
    $html = '<html>';
    $html .= '<head>';
    $html .= '   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
    $html .= '   <title>Preview Index</title>';
    $html .= '</head>';
    $html .= '<body>';
    $html .= '<ul>';
    foreach( $links as $lang => $langName ) {
        $html .= "<li><a href=\"$lang\">$langName</a>";
    }
    $html .= '</ul>';
    $html .= '</body>';
    $html .= '</html>';
    file_put_contents( $preview_dir . DIRECTORY_SEPARATOR . 'index.html' , $html );
}

echo "\nDone\n";
