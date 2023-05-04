<?php
include_once("./string.php");
if (DEV_MODE == true) {
    $all_images = scandir("pwa-public-icons");
    foreach ($all_images as $file) {
        if ($file === "." || $file === "..") {
            continue;
        } else {
            unlink("pwa-public-icons/$file");
        }
    }
    rmdir("pwa-public-icons");
    $image_sizes = [72, 96, 128, 144, 152, 192, 384, 512];
    $file = PWA_ICON;
    $source_properties = getimagesize($file);
    $image_type = $source_properties[2];
    if ($image_type == IMAGETYPE_JPEG) {
        $image_resource_id = imagecreatefromjpeg($file);
    } elseif ($image_type == IMAGETYPE_GIF) {
        $image_resource_id = imagecreatefromgif($file);
    } elseif ($image_type == IMAGETYPE_PNG) {
        $image_resource_id = imagecreatefrompng($file);
    }
    mkdir("pwa-public-icons");
    foreach ($image_sizes as $size) {
        $target_layer = imagecreatetruecolor($size, $size);
        imagealphablending($target_layer, false);
        imagesavealpha($target_layer, true);
        $transparency = imagecolorallocatealpha($target_layer, 255, 255, 255, 127);
        imagefilledrectangle($target_layer, 0, 0, $size, $size, $transparency);
        imagecopyresampled($target_layer, $image_resource_id, 0, 0, 0, 0, $size, $size, $source_properties[0], $source_properties[1]);
        imagepng($target_layer, "./pwa-public-icons/{$size}x{$size}.png");
    }
    $json = [
        "name" => SITE_TITLE,
        "short_name" => strtolower(str_ireplace([" ", "a", "e", "i", "o", "u"], "", SITE_TITLE)),
        "description" => PWA_DESCRIPTION,
        "start_url" => "./",
        "theme_color" => THEME_COLOR,
        "display" => "standalone",
        "background_color" => PWA_BG_COLOR,
        "icons" => []
    ];
    foreach ($image_sizes as $size) {
        $icon_cred = [
            "src" => "./pwa-public-icons/{$size}x{$size}.png",
            "sizes" => "{$size}x{$size}",
            "type" => "image/png"
        ];
        array_push($json['icons'], $icon_cred);
    }
    $menifest_file = fopen("./manifest.json", "w");
    fwrite($menifest_file, json_encode($json));
    fclose($menifest_file);
    $files_to_cache = [];
    if (file_exists("./modules/chunk.css")) {
        array_push($files_to_cache, "\"./modules/chunk.css\"");
    }
    if (file_exists("./modules/chunk.js")) {
        array_push($files_to_cache, "\"./modules/chunk.js\"");
    }
    $mod_files = scandir("./modules");
    foreach ($mod_files as $file) {
        if (strpos($file, ".yugal.css")) {
            array_push($files_to_cache, "\"./modules/$file\"");
        }
    }
    $asset_files = scandir("./src/assets");
    foreach ($asset_files as $file) {
        if ($file === "." || $file === "..") {
            continue;
        } else {
            array_push($files_to_cache, "\"./src/assets/$file\"");
        }
    }

    $arraystring = "";
    foreach ($files_to_cache as $file) {
        $arraystring = "$file, " . $arraystring;
    }
    $additional_sw_code = "";
    if (defined("PWA_SW")) {
        $additional_sw_code = PWA_SW;
    }
    $cache_name = strtolower(str_ireplace([" ", "a", "e", "i", "o", "u"], "", SITE_TITLE));
    $sw = <<<JS
            const CACHE_NAME = "{$cache_name}-pwa-cache";
            const urlsToCache = [
            "./", "./modules/yugal.js", $arraystring
            ];

            self.addEventListener("install", (event) => {
            event.waitUntil(
                caches.open(CACHE_NAME).then((cache) => {
                return cache.addAll(urlsToCache);
                })
            );
            });

            self.addEventListener("fetch", (event) => {
            event.respondWith(
                caches.match(event.request).then((response) => {
                if (response) {
                    return response;
                }
                return fetch(event.request);
                })
            );
            });

            self.addEventListener("activate", (event) => {
            const cacheWhitelist = [CACHE_NAME];
            event.waitUntil(
                caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                    if (!cacheWhitelist.includes(cacheName)) {
                        return caches.delete(cacheName);
                    }
                    })
                );
                })
            );
            });
            $additional_sw_code

        JS;
    $fsw = fopen("./pwa-sw.js", "w");
    fwrite($fsw, $sw);
    fclose($fsw);
}

script(<<<JS
    const pwa = {
        beforeInstallPrompt: null,
        eventHandler: (event) => {
            pwa.beforeInstallPrompt = event;
        },
        install: () =>{
            if (pwa.beforeInstallPrompt){
                pwa.beforeInstallPrompt.prompt();
            }else{
                console.warn("PROMPTING PWA WITHOUT USER GESTURE IS NOT ALLOWED IN CHROME, SETUP THIS PROMPT WHEN SOME BUTTON IS CLICKED.")
            }
        }
    };
        window.addEventListener("beforeinstallprompt", (event)=>{
            pwa.eventHandler(event);
        });
JS);
?>