# This is for my educational purpose
creating messenger bot and exploring facebook's developer tools

# Install NodeJS
#$ sudo apt-get update
#$ curl -sL https://deb.nodesource.com/setup_7.x | sudo -E bash -
$ sudo apt-get install -y nodejs
$ sudo apt-get install -y build-essential
$ sudo apt-get install npm

# Create Heroku account and Install heroku service
$ sudo add-apt-repository "deb https://cli-assets.heroku.com/branches/stable/apt ./"
$ curl -L https://cli-assets.heroku.com/apt/release.key | sudo apt-key add -
$ sudo apt-get update
$ sudo apt-get install heroku
$ heroku login
$ heroku apps:create "apk-messenger-bot"

# Initialize npm
$ npm init
$ npm install express body-parser request --save

# Create Facebook APP and Page
# Enable messenger service, add webhook url https://apk-messenger-bot.herokuapp.com/webhook
