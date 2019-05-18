/**
 * @file
 * @preserve
 * DOM content synchronization with Mustache templates.
 *
 * Using Mustache.js by
 *   Copyright (c) 2009 Chris Wanstrath (Ruby)
 *   Copyright (c) 2010-2014 Jan Lehnardt (JavaScript)
 *   Copyright (c) 2010-2015 The mustache.js community
 */

window.mustacheSync = window.mustacheSync || {items: [], templates: []};

(function (Drupal, sync, Mustache, window) {
  sync.registry = sync.registry || {
    items: [],
    pending: [],
    templates: {},
    providers: {},
    listeners: {}
  };

  sync.___internals = sync.___internals || {
    subset: function (data, select) {
      var subset = data;
      var slength = select.length;
      var skey;
      var i;
      for (i = 0; i < slength; i++) {
        skey = select[i];
        if (subset.hasOwnProperty(skey)) {
          subset = subset[skey];
        }
        else {
          return false;
        }
      }
      return subset;
    },
    isEmpty: function (obj) {
      var k;
      if (obj === false || obj === null) {
        return true;
      }
      for (k in obj) {
        if (obj.hasOwnProperty(k)) {
          return false;
        }
      }
      return true;
    },
    trigger: function (target, type, canBubble, cancelable, detail) {
      var event = window.document.createEvent('CustomEvent');
      if (typeof detail === 'undefined') {
        detail = null;
      }
      event.initCustomEvent(type, canBubble, cancelable, detail);
      target.dispatchEvent(event);
    },
    item: {
      update: function (period, force_fetch, no_repeat) {
        var provider = this.provider;
        var fetch = sync.___internals.item.fetch;
        var render = sync.___internals.item.render;
        var next;
        if (typeof period === 'undefined') {
          period = this.period;
        }
        if (no_repeat !== true) {
          no_repeat = false;
        }

        if (provider !== null) {
          if (provider.faulty === true) {
            if (period > 0) {
              // Retry when period is set.
              this.sync(this.delay + 1000, period, false, true, no_repeat);
            }
            return;
          }
          if (provider.fetching === true) {
            // Retry.
            this.sync(this.delay + 10, period, false, true, no_repeat);
            return;
          }
          if ((provider.fetched === null) || ((period > 0) && (period - 1 < Date.now() - provider.fetched)) || (force_fetch === true)) {
            fetch.call(this, period, no_repeat);
            return;
          }
        }

        render.call(this);
        if (!no_repeat) {
          // Repeat or sync next one.
          next = this.next();
          if (next) {
            next.sync(period, period);
          }
        }
      },
      fetch: function (period, no_repeat) {
        var provider = this.provider;
        var fetch = sync.___internals.item.fetch;
        var render = sync.___internals.item.render;
        var isEmpty = sync.___internals.isEmpty;
        var request = new XMLHttpRequest();

        provider.fetching = true;
        if (typeof period === 'undefined') {
          period = this.period;
        }
        if (no_repeat !== true) {
          no_repeat = false;
        }

        request.open('GET', provider.url, true);
        request.onload = function () {
          var data = false;
          var next = false;
          if (request.status >= 200 && request.status < 400) {
            try {
              data = JSON.parse(request.responseText);
              provider.latest = data;
              provider.fetched = Date.now();
            }
            catch (e) {
              data = false;
            }
          }
          else if (request.status >= 500) {
            request.onerror();
            return;
          }
          provider.fetching = false;
          provider.faulty = false;
          if (this.increment === null || !isEmpty(data)) {
            render.call(this);
          }
          if (!no_repeat) {
            if (this.increment !== null && this.increment.loop && isEmpty(data)) {
              next = this.next(0);
            }
            else {
              next = this.next();
            }
            if (next) {
              next.sync(period, period);
            }
          }
        }.bind(this);
        request.onerror = function () {
          provider.fetching = false;
          provider.faulty = true;
          if (period > 0) {
            // Retry when period is set.
            window.setTimeout(fetch.bind(this, period, no_repeat), period + 5000);
          }
        }.bind(this);

        request.send();
      },
      done: function () {
        if (this.increment !== null) {
          if (this.increment.i + 1 >= this.increment.max && !this.increment.loop) {
            return true;
          }
        }
        return this.limit === 0 || (this.period === 0 && this.limit < -1);
      },
      next: function (index) {
        if (this.done()) {
          return false;
        }
        if (this.increment !== null) {
          if (typeof index === 'number') {
            this.increment.i = index;
            this.increment.value = this.increment.offset + (index * this.increment.step);
          }
          else if (this.increment.i + 1 >= this.increment.max) {
            if (this.increment.loop) {
              this.increment.i = 0;
              this.increment.value = this.increment.offset;
            }
          }
          else {
            this.increment.i++;
            this.increment.value += this.increment.step;
          }
          if (this.provider !== null) {
            this.provider.reset();
            this.provider = this.provider.rebuild(this.increment.key, this.increment.value);
          }
          else {
            this.data[this.increment.key] = this.increment.value;
          }
        }
        return this;
      },
      render: function () {
        var rendered;
        var el = this.element;
        var data = this.data;
        var select = this.select;
        var adjacent = this.adjacent;
        var subset = sync.___internals.subset;
        var trigger = sync.___internals.trigger;

        if (this.provider !== null) {
          data = this.provider.latest;
        }

        if (select !== null && typeof select === 'object' && data !== null && typeof data === 'object') {
          data = subset(data, select);
        }
        if (!(typeof data === 'object')) {
          if (el.classList) {
            el.classList.remove('syncing');
            el.classList.remove('synced');
            el.classList.add('not-synced');
            el.classList.add('error');
          }
          else {
            el.className = el.className.replace(new RegExp('(^|\\b)syncing(\\b|$)', 'gi'), ' ');
            el.className = el.className.replace(new RegExp('(^|\\b)synced(\\b|$)', 'gi'), ' ');
            el.className += ' not-synced error';
          }
          return;
        }
        rendered = Mustache.render(sync.registry.templates[this.template], data);
        if (this.behaviors) {
          Drupal.detachBehaviors(el);
        }
        if (this.eval) {
          switch (adjacent) {
            case 'beforebegin':
              this.$el.before(rendered);
              break;
            case 'afterbegin':
              this.$el.prepend(rendered);
              break;
            case 'beforeend':
              this.$el.append(rendered);
              break;
            case 'afterend':
              this.$el.after(rendered);
              break;
            default:
              this.$el.html(rendered);
          }
        }
        else {
          switch (adjacent) {
            case 'beforebegin':
            case 'afterbegin':
            case 'beforeend':
            case 'afterend':
              el.insertAdjacentHTML(adjacent, rendered);
              break;
            default:
              el.innerHTML = rendered;
          }
        }
        if (el.classList) {
          el.classList.add('synced');
          el.classList.remove('syncing');
          el.classList.remove('not-synced');
          el.classList.remove('error');
        }
        else {
          el.className += ' synced';
          el.className = el.className.replace(new RegExp('(^|\\b)syncing(\\b|$)', 'gi'), ' ');
          el.className = el.className.replace(new RegExp('(^|\\b)not-synced(\\b|$)', 'gi'), ' ');
          el.className = el.className.replace(new RegExp('(^|\\b)error(\\b|$)', 'gi'), ' ');
        }
        trigger(el, 'mustacheSyncFinish', true, false, this);
        if (this.behaviors) {
          Drupal.attachBehaviors(el);
        }
      },
      init: function () {
        var internals = sync.___internals.item;
        var getProvider = sync.___internals.provider.get;

        if (this.initialized === true) {
          // Already initialized, aborting.
          return;
        }

        // Initialize the provider, if given.
        if (typeof this.data === 'string') {
          this.provider = getProvider(this.data);
        }
        else {
          this.data = this.data || {};
          this.provider = null;
        }

        // Initialize the increment, if given.
        this.increment = this.increment || null;
        if (this.increment !== null) {
          this.increment.offset = this.increment.offset || 0;
          this.increment.key = this.increment.key || 'page';
          this.increment.value = this.increment.offset;
          this.increment.step = this.increment.step || 1;
          this.increment.max = this.increment.max || -1;
          this.increment.i = 0;
          this.increment.loop = this.increment.loop || true;
          if (this.provider !== null) {
            this.provider = this.provider.rebuild(this.increment.key, this.increment.value);
          }
          else {
            this.data[this.increment.key] = this.increment.value;
          }
        }

        this.listen = this.listen || internals.listen.bind(this);
        this.ready = this.ready || internals.ready.bind(this);
        this.sync = this.sync || internals.sync.bind(this);
        this.done = this.done || internals.done.bind(this);
        this.next = this.next || internals.next.bind(this);

        this.delay = this.delay || 0;
        this.period = this.period || 0;
        this.limit = this.limit || -1;
        this.trigger = this.trigger || null;
        this.adjacent = this.adjacent || null;
        this.eval = (this.eval === true) || false;
        this.behaviors = (this.behaviors === true) || (this.eval && this.behaviors !== false) || false;

        this.started = false;
        this.triggered = false;
        this.initialized = true;
      },
      ready: function () {
        var template_exists = sync.registry.templates.hasOwnProperty(this.template);
        if (this.eval) {
          // Script execution requires jQuery.
          if (window.jQuery && !this.$el) {
            this.$el = window.jQuery(this.element);
          }
          return template_exists && this.$el;
        }
        return template_exists;
      },
      start: function () {
        if (!this.ready() || this.started === true) {
          return;
        }
        this.started = true;

        if (this.trigger === null) {
          // Synchronize immediately, or at least after a specified delay.
          this.sync(this.delay, this.period);
        }
        else {
          // Listen and synchronize by the specified triggers.
          this.listen(this.trigger);
        }
      },
      listen: function (triggers) {
        var listeners = sync.registry.listeners;
        var trigger;
        var selector;
        var event;
        var limit;
        var group;
        var subscribers;
        var i;

        // Add this item as a subscriber for each specified triggering element.
        for (i = 0; i < triggers.length; i++) {
          trigger = triggers[i];
          selector = trigger[0];
          event = trigger[1];
          limit = trigger[2];
          if (!listeners.hasOwnProperty(selector)) {
            listeners[selector] = {
              elements: [],
              events: {}
            };
          }
          group = listeners[selector];
          if (!group.events.hasOwnProperty(event)) {
            group.events[event] = {
              triggered: function () {
                var i;
                var subscriber;
                var item;
                var length = this.subscribers.length;

                for (i = 0; i < length; i++) {
                  subscriber = this.subscribers[i];
                  item = subscriber.item;
                  if (subscriber.limit === 0) {
                    continue;
                  }
                  subscriber.limit--;
                  if (item.triggered === true) {
                    if (item.limit < 0) {
                      item.limit = -1;
                    }
                    item = item.next();
                    if (item) {
                      subscriber.item = item;
                      item.sync(item.delay, 0, true, false, true);
                    }
                  }
                  else {
                    item.triggered = true;
                    item.sync(item.delay, item.period, true);
                  }
                }
              },
              subscribers: []
            };
          }
          subscribers = group.events[event].subscribers;
          subscribers.push({item: this, limit: limit});
        }
      },
      sync: function (delay, period, force_fetch, ignore_limit, no_repeat) {
        var el = this.element;
        var trigger = sync.___internals.trigger;
        if (!this.ready()) {
          return;
        }
        if (ignore_limit !== true) {
          if (this.done()) {
            return;
          }
          this.limit--;
        }
        if (typeof delay === 'undefined') {
          delay = this.delay;
        }
        if (typeof period === 'undefined') {
          period = this.period;
        }
        if (force_fetch !== true) {
          force_fetch = false;
        }
        if (no_repeat !== true) {
          no_repeat = false;
        }
        if (el.classList) {
          el.classList.add('syncing');
          el.classList.remove('synced');
        }
        else {
          el.className += ' syncing';
          el.className = el.className.replace(new RegExp('(^|\\b)synced(\\b|$)', 'gi'), ' ');
        }
        trigger(el, 'mustacheSyncBegin', true, false, this);
        window.setTimeout(sync.___internals.item.update.bind(this, period, force_fetch, no_repeat), delay);
      }
    },
    provider: {
      init: function (url) {
        var internals = sync.___internals.provider;
        var instance = {
          url: url,
          latest: {},
          fetched: null,
          fetching: false,
          faulty: false
        };
        instance.getParts = internals.parts.bind(instance);
        instance.getParams = internals.params.bind(instance);
        instance.rebuild = internals.rebuild.bind(instance);
        instance.reset = internals.reset.bind(instance);
        return instance;
      },
      get: function (url) {
        var providers = sync.registry.providers;
        if (!providers.hasOwnProperty(url)) {
          providers[url] = sync.___internals.provider.init(url);
        }
        return providers[url];
      },
      rebuild: function (key, val) {
        var getProvider = sync.___internals.provider.get;
        var buffer;
        var other;
        var parts;
        var params;

        this.getParams();
        if (this.params.hasOwnProperty(key)) {
          if ((typeof this.params[key] === 'string') && (typeof val === 'number')) {
            this.params[key] = parseInt(this.params[key]);
          }
          if (this.params[key] === val) {
            return getProvider(this.url);
          }
        }

        params = {};
        params[key] = val;
        buffer = {paramKey: null, search: '?', params: [key + '=' + val]};
        for (buffer.paramKey in this.params) {
          if (!(buffer.paramKey === key) && this.params.hasOwnProperty(buffer.paramKey)) {
            buffer.params.push(buffer.paramKey + '=' + this.params[buffer.paramKey]);
            params[buffer.paramKey] = this.params[buffer.paramKey];
          }
        }
        buffer.search += buffer.params.join('&');

        parts = window.document.createElement('a');
        parts.href = this.url;
        parts.search = buffer.search;

        other = getProvider(parts.href);
        other.parts = parts;
        other.params = params;
        return other;
      },
      parts: function () {
        if (!this.hasOwnProperty('parts')) {
          // Extract and attach the url parts.
          this.parts = window.document.createElement('a');
          this.parts.href = this.url;
        }
        return this.parts;
      },
      params: function () {
        var buffer;
        if (!this.hasOwnProperty('params')) {
          // Extract and attach the query parameters.
          this.params = {};
          buffer = {search: this.getParts().search};
          buffer.search = buffer.search.substring(1);
          if (buffer.search.length === 0) {
            // No params given, abort extracting.
            return this.params;
          }
          buffer.queries = buffer.search.split('&');
          buffer.ql = buffer.queries.length;
          for (buffer.i = 0; buffer.i < buffer.ql; buffer.i++) {
            buffer.current = buffer.queries[buffer.i].split('=');
            if (buffer.current[0].length === 0) {
              continue;
            }
            if (buffer.current.length === 2) {
              this.params[buffer.current[0]] = buffer.current[1];
            }
            else {
              this.params[buffer.current[0]] = '';
            }
          }
        }
        return this.params;
      },
      reset: function () {
        if (!this.faulty) {
          this.latest = {};
          this.fetched = null;
          this.fetching = false;
        }
      }
    }
  };

  sync.now = sync.now || function () {
    var registry = sync.registry;
    var init = sync.___internals.item.init;
    var start = sync.___internals.item.start;
    var item;
    var template;
    var i;

    i = sync.templates.length;
    while (i > 0) {
      i--;
      template = sync.templates.shift();
      registry.templates[template.name] = template.content;
    }

    i = registry.pending.length;
    while (i > 0) {
      i--;
      item = registry.pending.shift();
      if (item.ready()) {
        start.call(item);
      }
      else {
        registry.pending.push(item);
      }
    }

    i = sync.items.length;
    while (i > 0) {
      i--;
      item = sync.items.shift();
      init.call(item);
      registry.items.push(item);
      if (item.ready()) {
        start.call(item);
      }
      else {
        registry.pending.push(item);
      }
    }
  };
  sync.refresh = sync.refresh || function (dom) {
    var listeners = sync.registry.listeners;
    var selector;
    var group;
    var new_elements;
    var event_name;
    var event_item;
    var i;
    var el;
    var length;

    sync.now();

    if (typeof dom === 'undefined') {
      dom = window.document;
    }
    else if (dom === null) {
      return;
    }

    // Collect all triggering elements,
    // and register the corresponding event listeners.
    for (selector in listeners) {
      if (!listeners.hasOwnProperty(selector)) {
        continue;
      }
      group = listeners[selector];

      new_elements = dom.querySelectorAll(selector);
      length = new_elements.length;
      for (i = 0; i < length; i++) {
        el = new_elements[i];
        if (group.elements.indexOf(el) < 0) {
          group.elements.push(el);
        }
        for (event_name in group.events) {
          if (!group.events.hasOwnProperty(event_name)) {
            continue;
          }
          event_item = group.events[event_name];

          length = group.elements.length;
          for (i = 0; i < length; i++) {
            group.elements[i].addEventListener(event_name, event_item.triggered.bind(event_item), false);
          }
        }
      }
    }
  };

  sync.now();

  Drupal.behaviors.mustacheSync = {
    attach: function attach(dom) {
      sync.refresh(dom);
    }
  };
}(Drupal, window.mustacheSync, window.Mustache, window));
