## Prerequisites
- installed apache + php (>=7.3)
- installed redis server
- installed node (>=12.4) and yarn

## Installation
### API backend
- point the document root to the `public` directory in the web server
- run `composer install --no-dev`
- make a copy of the .env file named .env.local and edit the necessary parameters
- set some cron jobs (change the project paths if necessary):
```
* *   * * *   root    cd /project && php /project/bin/console check:events
0 0   * * *   root    cd /project && php /project/bin/console watch:events
15 0   * * *   root    cd /project && php /project/bin/console refresh:events
```
- Optional: you can change the branding images in the `public/images` directory to your needs.
### Websocket backend
- cd ws-node
- run `yarn install`
- make a copy the .env.dist file as .env and edit it
- run `node ws.js`
