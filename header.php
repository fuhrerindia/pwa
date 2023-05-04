<link rel="manifest" href="./manifest.json"><?php 
script(<<<JS
if ("serviceWorker" in navigator) {
        window.addEventListener("load", () => {
          navigator.serviceWorker.register("./pwa-sw.js");
        });
      }
JS);
?>