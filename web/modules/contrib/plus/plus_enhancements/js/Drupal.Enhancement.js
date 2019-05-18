/**
* DO NOT EDIT THIS FILE.
* THIS FILE IS COMPILED AUTOMATICALLY FROM ITS ES6 SOURCE.
* @preserve
**/'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

(function ($, Drupal) {
  'use strict';

  var _$element = new WeakMap();
  var _$wrapper = new WeakMap();
  var _attachments = new WeakMap();
  var _deferred = new WeakMap();
  var _detachments = new WeakMap();
  var _debug = new WeakMap();
  var _id = new WeakMap();
  var _initHandlers = new WeakMap();
  var _parent = new WeakMap();
  var _settings = new WeakMap();
  var _initialized = new WeakMap();
  var _storage = new WeakMap();

  var Enhancement = function () {
    function Enhancement(id) {
      var settings = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      var parent = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;

      _classCallCheck(this, Enhancement);

      _debug.set(this, false);
      _id.set(this, id);
      _initialized.set(this, false);
      _parent.set(this, parent);
      _settings.set(this, settings);
      _storage.set(this, Drupal.Storage.create('Drupal.Enhancement.' + id));

      this.$element = Drupal.$noop;

      this.$wrapper = Drupal.$noop;

      this.attachments = {};

      this.deferred = {};

      this.detachments = {};

      if (parent) {
        this.extend(parent);
      }
    }

    _createClass(Enhancement, [{
      key: 'attach',
      value: function attach(selectors, callback) {
        var _this = this,
            _arguments = arguments;

        this.attachments[selectors] = function (context, settings) {
          var $selectors = $(context).find(selectors).filter(function () {
            return !$(_this).data('Drupal.Enhancement.' + _this.id);
          });
          if ($selectors[0]) {
            _this.$selectors = $selectors;
            _this.__args__ = _arguments;
            _this.$selectors.data('Drupal.Enhancement.' + _this.id, _this);
            callback.apply(_this, [_this.$selectors, _this.settings]);
            delete _this.__args__;
          }
        };
        return this;
      }
    }, {
      key: 'attachElements',
      value: function attachElements(method, selectors, callback) {
        var _this2 = this,
            _arguments2 = arguments;

        this.attach(selectors, function ($selectors) {
          var parts = (method + ':*').split(':');
          var filter = parts[1] === '*' ? parts[1] : ':' + parts[1];
          method = parts[0];
          if (_this2.$wrapper[0]) {
            _this2.$wrapper.append(_this2.$element);
            $selectors.filter(filter)[method](_this2.$wrapper);
          } else {
            $selectors.filter(filter)[method](_this2.$element);
          }
          if (callback) {
            callback.apply(_this2, _arguments2);
          }
        });
        return this;
      }
    }, {
      key: 'detach',
      value: function detach(selectors, callback) {
        var _this3 = this,
            _arguments3 = arguments;

        this.detachments[selectors] = function (context, settings, trigger) {
          var $selectors = $(context).find(selectors);
          if ($selectors[0]) {
            _this3.__args__ = _arguments3;
            _this3.$selectors = $selectors;
            callback.apply(_this3, [_this3.$selectors, _this3.settings]);
            _this3.$selectors.removeData('Drupal.Enhancement.' + _this3.id);
            delete _this3.__args__;
          }
        };
        return this;
      }
    }, {
      key: 'detachElements',
      value: function detachElements(selectors, callback) {
        var _this4 = this,
            _arguments4 = arguments;

        this.detach(selectors, function () {
          _this4.$element.remove();
          _this4.$wrapper.remove();
          if (callback) {
            callback.apply(_this4, _arguments4);
          }
        });
        return this;
      }
    }, {
      key: 'error',
      value: function error(message, args) {
        Drupal.error(message, args);
      }
    }, {
      key: 'extend',
      value: function extend() {
        var _this5 = this;

        for (var _len = arguments.length, args = Array(_len), _key = 0; _key < _len; _key++) {
          args[_key] = arguments[_key];
        }

        var deep = args[0] === true || args[0] === false ? args.shift() : false;
        args.forEach(function (obj) {
          Object.keys(obj).forEach(function (key) {
            if (!deep && !obj.hasOwnProperty(key)) {
              return;
            }
            _this5[key] = obj[key];
          });
        });
        return this;
      }
    }, {
      key: 'fatal',
      value: function fatal(message, args) {
        return Drupal.fatal(message, args);
      }
    }, {
      key: 'getSetting',
      value: function getSetting(name, defaultValue) {
        var settings = this.settings;
        if (settings[name] === undefined || settings[name] === null) {
          return typeof defaultValue === 'function' ? defaultValue.call(this) : defaultValue;
        }
        return settings[name];
      }
    }, {
      key: 'info',
      value: function info(message, args) {
        if (this.debug) {
          Drupal.info(message, args);
        }
      }
    }, {
      key: 'init',
      value: function init() {
        var _this6 = this;

        var callback = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;

        if (_initialized.get(this)) {
          return this;
        }
        var handlers = _initHandlers.get(this) || new Set();
        if (callback) {
          handlers.add(callback);
        } else {
          handlers.forEach(function (handler) {
            handler.call(_this6);
          });
          handlers.clear();
          _initialized.set(this, true);
        }
        _initHandlers.set(this, handlers);
        return this;
      }
    }, {
      key: 'namespaceEventType',
      value: function namespaceEventType(type) {
        var _this7 = this;

        type = type || '';
        var types = type.split(' ');
        types.forEach(function (type, i) {
          var namespaced = type.split('.');
          namespaced.push(_this7.id);
          types[i] = namespaced.join('.');
        });
        return types;
      }
    }, {
      key: 'off',
      value: function off(type) {
        var namespaced = this.namespaceEventType(type);

        if (this.$element[0]) {
          var attributes = this.$element[0].attributes;
          for (var i in attributes) {
            if (!attributes.hasOwnProperty(i)) {
              continue;
            }
            var name = attributes[i].name;

            if (!/^data-user-enhancement-/.test(name)) {
              continue;
            }

            if (new RegExp(type ? namespaced.join('-').replace(/\./g, '-') : this.id.replace(/\./g, '-')).test(name)) {
              attributes.removeNamedItem(name);
            }
          }
        }

        Drupal.$document.off(namespaced.join(' '));
        return this;
      }
    }, {
      key: 'on',
      value: function on(type, handler) {
        var namespaced = this.namespaceEventType(type);
        var dataAttribute = 'data-user-enhancement-' + namespaced.join('-').replace(/\./g, '-');
        this.$element.attr(dataAttribute, 'true');
        Drupal.$document.on(namespaced.join(' '), '[' + dataAttribute + ']', handler.bind(this));
        return this;
      }
    }, {
      key: 'parseArguments',
      value: function parseArguments(args, bind) {
        var _this8 = this;

        args = [].concat(_toConsumableArray(args));
        if (bind === void 0 || bind) {
          args.forEach(function (arg, i) {
            if (typeof arg === 'function') {
              args[i] = arg.bind(_this8);
            }
          });
        }
        return args;
      }
    }, {
      key: 'ready',
      value: function ready(callback) {
        var o = {};
        o[this.random('__ready__')] = callback;
        this.attachments = _extends({}, o, this.attachments);
        return this;
      }
    }, {
      key: 'setSetting',
      value: function setSetting(name, value) {
        var _this9 = this;

        var settings = _settings.get(this);

        var obj = _extends({}, (typeof name === 'undefined' ? 'undefined' : _typeof(name)) === 'object' ? name : {});
        if (typeof name === 'string') {
          obj[name] = value;
        }

        Object.keys(obj).forEach(function (key) {
          settings[key] = Drupal.typeCast(_this9.getSetting(key), obj[key]);
        });

        _settings.set(this, settings);

        return this;
      }
    }, {
      key: 'trigger',
      value: function trigger() {
        for (var _len2 = arguments.length, args = Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
          args[_key2] = arguments[_key2];
        }

        this.$element.trigger.apply(this.$element, this.parseArguments(args));
        return this;
      }
    }, {
      key: 'warning',
      value: function warning(message, args) {
        if (this.debug) {
          Drupal.warn(message, args);
        }
      }
    }, {
      key: '$element',
      get: function get() {
        return _$element.get(this);
      },
      set: function set(value) {
        if (!(value instanceof $)) {
          return Drupal.fatal(Drupal.t('A user enhancement $element must be a jQuery object.'));
        }
        return _$element.set(this, value);
      }
    }, {
      key: '$wrapper',
      get: function get() {
        return _$wrapper.get(this);
      },
      set: function set(value) {
        if (!(value instanceof $)) {
          return Drupal.fatal(Drupal.t('A user enhancement $wrapper must be a jQuery object.'));
        }
        return _$wrapper.set(this, value);
      }
    }, {
      key: 'attachments',
      get: function get() {
        return _attachments.get(this);
      },
      set: function set(value) {
        return _attachments.set(this, value);
      }
    }, {
      key: 'deferred',
      get: function get() {
        return _deferred.get(this);
      },
      set: function set(value) {
        return _deferred.set(this, value);
      }
    }, {
      key: 'detachments',
      get: function get() {
        return _detachments.get(this);
      },
      set: function set(value) {
        return _detachments.set(this, value);
      }
    }, {
      key: 'debug',
      get: function get() {
        return _debug.get(this);
      },
      set: function set(value) {
        _debug.set(this, Boolean(value));
      }
    }, {
      key: 'defaultSettings',
      get: function get() {
        return {};
      }
    }, {
      key: 'id',
      get: function get() {
        return _id.get(this);
      }
    }, {
      key: 'initialized',
      get: function get() {
        return _initialized.get(this);
      }
    }, {
      key: 'parent',
      get: function get() {
        return _parent.get(this);
      }
    }, {
      key: 'settings',
      get: function get() {
        var parent = this.parent;
        return $.extend(true, parent && parent.settings, this.defaultSettings, _settings.get(this));
      }
    }, {
      key: 'storage',
      get: function get() {
        return _storage.get(this);
      }
    }]);

    return Enhancement;
  }();

  Drupal.Enhancement = Enhancement;
})(jQuery, Drupal);