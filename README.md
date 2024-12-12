# Driver for Herd Laravel and PrestaShop Framework
If you are a PrestaShop and Laravel/Symfony developer using the Herd Laravel development environment, you'll know that without Apache, the .htaccess file directives are often ignored by NGINX. The solution is this Driver that simulates Apache redirects.

### Installation Steps:
1. **Download the `PrestaShopValetDriver.php` file**
2. **Access the Drivers folder:**
    - Go to the directory `~/Library/Application Support/Herd/config/valet/Drivers`.
   - If it doesn't exist, create it
    - You can do this quickly using the terminal with:
```bash
cd ~/Library/Application\ Support/Herd/config/valet/Drivers
```
3. **Copy the `PrestaShopValetDriver.php` file:**
    - Move or copy the file into this directory.
4. **Restart the Herd service or your Valet environment:**
    - To ensure the new driver is recognized, restart Herd or the Valet service using the command:
```bash
valet restart
```

### Purpose of the Driver:
The driver is specifically designed to identify and serve PrestaShop projects while using Laravel Valet. It automatically determines if a directory is a PrestaShop project and properly handles static requests (such as images or other static files) and dynamic requests.
After the driver has been installed and configured correctly, Herd Laravel will be able to run a PrestaShop site with native support.
## Additional Notes:

#### Compatibility: Works with PrestaShop 1.6.x, 1.7.x and 8.x
#### Requirements:
- Herd Laravel installed
- PHP 7.2 or higher
- Composer installed globally
