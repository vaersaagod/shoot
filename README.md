# Shoot plugin for Craft 3.x

## Installation

### Installing Chromium (Puppeteer) on the server (Ubuntu)

Install Chromium following the guide here: https://github.com/spatie/browsershot#requirements

```
curl -sL https://deb.nodesource.com/setup_12.x | sudo -E bash -
sudo apt-get install -y nodejs gconf-service libasound2 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 ca-certificates fonts-liberation libappindicator1 libnss3 lsb-release xdg-utils wget libgbm-dev
sudo npm install --global --unsafe-perm puppeteer
sudo chmod -R o+rx /usr/lib/node_modules/puppeteer/.local-chromium
```

### Installing and configuring the Shoot plugin

1. Add this to your `composer.json` file:

```composer
"minimum-stability": "dev",
"prefer-stable": true,
"repositories": {
   "vaersaagod/shoot": {
      "type": "git",
      "url": "git@bitbucket.org:vaersaagod/shoot.git",
      "trunk-path": "master"
   }
}
```

2. Run `composer require vaersaagod/shoot`

3. Copy the file `/vendor/vaersaagod/shoot/src/config.php` to `/config/shoot.php`, and configure the
   different settings as needed. Ideally, environment variables should be used, i.e.

`.env`:

```
SHOOT_SYSTEM_PATH="@webroot/shoot"
SHOOT_CHROMIUM_PATH=
SHOOT_PUBLIC_ROOT="@webroot"
SHOOT_BASE_URL="/shoot"
```

`config/shoot.php`:

```php
<?php

use craft\helpers\App;

return [
    'systemPath' => App::env('SHOOT_SYSTEM_PATH'),
    'chromiumPath' => App::env('SHOOT_CHROMIUM_PATH') ?: null,
    'publicRoot' => App::env('SHOOT_PUBLIC_ROOT'),
    'baseUrl' => App::env('SHOOT_BASE_URL'),
    'defaultExtension' => 'png',
];
```

4. Remember to add the path used for `systemPath` to your `.gitignore` file

## Usage

### Screenshot some HTML

```twig
{% set html %}
    <p>Look ma, no hands</p>
{% endset %}

{% set image = craft.shoot.html(html, { viewport: [800, 600] }) %}

<img src="{{ image.url }}" width="{{ image.width }}" height="{{ image.height }}" alt="" />

```

### Screenshot a URL

```twig
{% set image = craft.shoot.url('https://google.com', { viewport: [800, 600] }) %}

<img src="{{ image.url }}" width="{{ image.width }}" height="{{ image.height }}" alt="" />
```

### Screenshot a Twig template

Note: Add template variables by using the optional, third parameter

```twig
{% set image = craft.shoot.template('_pages/awesome-stuff', { viewport: [800, 600] }, { foo: 'bar' }) %}

<img src="{{ image.url }}" width="{{ image.width }}" height="{{ image.height }}" alt="" />
```

### Options

Note: All Shoot methods (`html`, `url` and `template`) take an optional, second argument which is an array
of Browsershot options:

`viewport` array, `[width, height]`  
`clip` array `[x, y, width, height]`  
`retina` bool (default `false`)  
`deviceScaleFactor` float (default `1`)  
`mobile` bool, simulates mobile device (default `false`)  
`touch` bool, simulates touch device (default `false`)  
`userAgent` string, spoof a user agent (default `null`)  
`hideBackground` bool, ignores the website's background image/color (default `false`)  
`fullPage` bool (default `false`)  
`delay` float (default `0`)  
