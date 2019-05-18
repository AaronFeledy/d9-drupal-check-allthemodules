const fs = require('fs');

const argv = require('minimist')(process.argv.slice(2));
const chokidar = require('chokidar');
const babel = require('babel-core');
const babelPolyfill = require('babel-polyfill');
const chalk = require('chalk');
const glob = require('glob');


// Match only on .es6.js files.
const fileMatch = './**/*.es6.js';
// Ignore everything in node_modules
const globOptions = {
  ignore: './node_modules/**'
};

// Log human-readable timestamp.
const log = (message) => {
  console.log(`[${new Date().toTimeString().slice(0, 8)}] ${message}`);
};

// Compile.
const compile = (filePath, callback) => {
  // Transform the file.
  // Check process.env.NODE_ENV to see if we should create sourcemaps.
  babel.transformFile(
    filePath,
    {
      sourceMaps: process.env.NODE_ENV === 'development' ? 'inline' : false,
      comments: false,
      "presets": [
        [
          "env",
          {
            "targets": {
              "browsers": [
                "last 2 versions",
                "ie >= 10"
              ]
            },
            "useBuiltIns": "entry"
          }
        ]
      ],
      plugins: [
        [
          'add-header-comment', {
          'header': [
            `DO NOT EDIT THIS FILE.\nTHIS FILE IS COMPILED AUTOMATICALLY FROM ITS ES6 SOURCE.\n@preserve`
          ]
        }],
        'transform-object-rest-spread',
      ]
    },
    (err, result) => {
      if (err) {
        log(chalk.red(err));
        process.exitCode = 1;
      }
      else {
        callback(result.code);
      }
    }
  );
};

const check = (filePath) => {
  log(`'${filePath}' is being checked.`);
  // Transform the file.
  compile(filePath, function check(code) {
    const fileName = filePath.slice(0, -7);
    fs.readFile(`${fileName}.js`, function read(err, data) {
      if (err) {
        log(chalk.red(err));
        process.exitCode = 1;
        return;
      }
      if (code !== data.toString()) {
        log(chalk.red(`'${filePath}' is not updated.`));
        process.exitCode = 1;
      }
    });
  });
};


const changeOrAdded = (filePath) => {
  log(`'${filePath}' is being processed.`);
  // Transform the file.
  compile(filePath, function write(code) {
    const fileName = filePath.slice(0, -7);
    // Write the result to the filesystem.
    fs.writeFile(`${fileName}.js`, code, () => {
      log(`'${filePath}' is finished.`);
    });
  });
};


/**
 * Provides the build command to compile *.es6.js files to ES5.
 *
 * Run build:js with --file to only parse a specific file. Using the --check
 * flag build:js can be run to check if files are compiled correctly.
 * @example <caption>Only process misc/drupal.es6.js and misc/drupal.init.es6.js</caption
 * yarn run build:js -- --file misc/drupal.es6.js --file misc/drupal.init.es6.js
 * @example <caption>Check if all files have been compiled correctly</caption
 * yarn run build:js -- --check
 *
 * @internal This file is part of the core javascript build process and is only
 * meant to be used in that context.
 */
const build = () => {
  'use strict';
  const processFiles = (error, filePaths) => {
    if (error) {
      process.exitCode = 1;
    }
    // Process all the found files.
    let callback = changeOrAdded;
    if (argv.check) {
      callback = check;
    }
    filePaths.forEach(callback);
  };

  if (argv.file) {
    processFiles(null, [].concat(argv.file));
  }
  else {
    glob(fileMatch, globOptions, processFiles);
  }
  process.exitCode = 0;
};


/**
 * Watch changes to *.es6.js files and compile them to ES5 during development.
 *
 * @internal This file is part of the core javascript build process and is only
 * meant to be used in that context.
 */

const watch = () => {
  'use strict';

// Match only on .es6.js files.
// Ignore everything in node_modules
  const watcher = chokidar.watch(fileMatch, {
    ignoreInitial: true,
    ignored: './node_modules/**'
  });

  const unlinkHandler = (err) => {
    if (err) {
      log(err);
    }
  };

// Watch for filesystem changes.
  watcher
    .on('add', changeOrAdded)
    .on('change', changeOrAdded)
    .on('unlink', (filePath) => {
      const fileName = filePath.slice(0, -7);
      fs.stat(`${fileName}.js`, () => {
        fs.unlink(`${fileName}.js`, unlinkHandler);
      });
      fs.stat(`${fileName}.js.map`, () => {
        fs.unlink(`${fileName}.js.map`, unlinkHandler);
      });
    })
    .on('ready', () => log(`Watching '${fileMatch}' for changes.`));
};

const command = argv._.shift();
const args = argv._;

switch (command) {
  case 'build':
    build(...args);
    break;

  case 'watch':
    build(...args);
    break;

  default:
    console.error(`Unknown command: ${command}`);
}
