
1. Upload in installer
2. Go to modifications > refresh
3. Go to extensions > extensions > modules
4. Install Custom Product Designer module
5. Enable It 
6. Add Designer URL ( you can ask for it by emailing us at support@iextendlabs.com with your purchase order id or email you used to buy extension)

Add to your .htaccess file this too allow designer to user your site mockup images
<FilesMatch "\.(jpg|jpeg|png|gif|webp|svg)$">
  Header set Access-Control-Allow-Origin "{{DesignerURL}}"
</FilesMatch>
