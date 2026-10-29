<?php
/** Build-time GD conversion; runtime import does not require an image encoder. */
$root=dirname(__DIR__);$dir=$root.'/zarincode/assets/images';$assets=[];
foreach(glob($dir.'/*') as $file){
    if(!preg_match('/\.(jpe?g|png)$/i',$file))continue;
    $image=imagecreatefromstring(file_get_contents($file));
    if(!$image)throw new RuntimeException('Decode failed: '.basename($file));
    if(!imageistruecolor($image))imagepalettetotruecolor($image);
    imagealphablending($image,false);imagesavealpha($image,true);
    $name=pathinfo($file,PATHINFO_FILENAME).'.webp';$destination=$dir.'/'.$name;
    if(!imagewebp($image,$destination,90))throw new RuntimeException('Encode failed: '.$name);
    $check=imagecreatefromwebp($destination);
    if(!$check||imagesx($check)!==imagesx($image)||imagesy($check)!==imagesy($image))throw new RuntimeException('Verification failed: '.$name);
    $assets[$name]=['legacy'=>basename($file),'width'=>imagesx($check),'height'=>imagesy($check),'bytes'=>filesize($destination),'sha256'=>hash_file('sha256',$destination)];
    unset($image,$check);
}
ksort($assets);file_put_contents($root.'/zarincode/demo-data/media-manifest.json',json_encode($assets,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
echo 'PASS: '.count($assets)." real WebP files encoded and decoded.\n";
