# Headless WordPress Theme (Rest API)
A WordPress Theme which doesn't show any content in the frontend and is completely blank. Since the Backend (exampledomain.de/wp-content) is still available, you can use this WordPress Theme as headless CMS for any Javascript Framework you like (i.e. via RestAPI).

## Creating ZIP for upload through WP Admin

zip -vr headless-theme.zip headless-theme/ -x "*.git*"