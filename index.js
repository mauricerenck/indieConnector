(function() {
  "use strict";
  function normalizeComponent(scriptExports, render, staticRenderFns, functionalTemplate, injectStyles, scopeId, moduleIdentifier, shadowMode) {
    var options = typeof scriptExports === "function" ? scriptExports.options : scriptExports;
    if (render) {
      options.render = render;
      options.staticRenderFns = staticRenderFns;
      options._compiled = true;
    }
    return {
      exports: scriptExports,
      options
    };
  }
  const _sfc_main$5 = {
    props: {
      year: Number,
      month: Number,
      nextYear: Number,
      nextMonth: Number,
      prevYear: Number,
      prevMonth: Number,
      summary: Object,
      targets: Array,
      sources: Array,
      sent: Array
    },
    methods: {
      goToPrevMonth() {
        const panelPath = window.location.pathname.split("webmentions");
        window.location.pathname = `${panelPath[0]}webmentions/${this.prevYear}/${this.prevMonth}`;
      },
      goToNextMonth() {
        const panelPath = window.location.pathname.split("webmentions");
        window.location.pathname = `${panelPath[0]}webmentions/${this.nextYear}/${this.nextMonth}`;
      }
    }
  };
  var _sfc_render$5 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("k-inside", [_c("k-view", { staticClass: "k-webmentions-view" }, [_c("k-header", [_vm._v("Webmentions")]), _c("div", { staticClass: "prev-next" }, [_c("button", { staticClass: "k-link k-button", on: { "click": _vm.goToPrevMonth } }, [_c("k-icon", { attrs: { "type": "angle-left" } })], 1), _vm._v(" " + _vm._s(_vm.month) + " / " + _vm._s(_vm.year) + " "), _c("button", { staticClass: "k-link k-button", on: { "click": _vm.goToNextMonth } }, [_c("k-icon", { attrs: { "type": "angle-right" } })], 1)]), _c("DetailsByMonth", { attrs: { "summary": _vm.summary } }), _c("Targets", { attrs: { "targets": _vm.targets } }), _c("Sources", { attrs: { "sources": _vm.sources } }), _c("Sent", { attrs: { "outbox": _vm.sent } })], 1)], 1);
  };
  var _sfc_staticRenderFns$5 = [];
  _sfc_render$5._withStripped = true;
  var __component__$5 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$5,
    _sfc_render$5,
    _sfc_staticRenderFns$5
  );
  __component__$5.options.__file = "/Users/mauricerenck/Sites/kirby-plugins/indieConnector2/src/components/View.vue";
  const View = __component__$5.exports;
  const _sfc_main$4 = {
    props: {
      summary: {
        summary: Number,
        likes: Number,
        replies: Number,
        reposts: Number,
        mentions: Number,
        bookmarks: Number
      }
    }
  };
  var _sfc_render$4 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", [_c("div", { staticClass: "wrapper" }, [_c("k-headline", { attrs: { "size": "large" } }, [_vm._v("Summary")]), _c("k-line-field"), _c("table", [_c("tr", [_c("td", [_vm._v(" " + _vm._s(_vm.summary.summary) + " "), _vm.summary.summary > 1 ? _c("span", [_vm._v("Webmentions")]) : _vm.summary.summary === 1 ? _c("span", [_vm._v("Webmention")]) : _vm._e()]), _c("td", [_c("div", { class: { dimmed: _vm.summary.likes === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-fav" } }), _vm._v(" " + _vm._s(_vm.summary.likes) + " ")], 1)]), _c("td", [_c("div", { class: { dimmed: _vm.summary.replies === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-reply" } }), _vm._v(" " + _vm._s(_vm.summary.replies) + " ")], 1)]), _c("td", [_c("div", { class: { dimmed: _vm.summary.reposts === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-repost" } }), _vm._v(" " + _vm._s(_vm.summary.reposts) + " ")], 1)]), _c("td", [_c("div", { class: { dimmed: _vm.summary.mentions === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-mention" } }), _vm._v(" " + _vm._s(_vm.summary.mentions) + " ")], 1)]), _c("td", [_c("div", { class: { dimmed: _vm.summary.bookmarks === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-bookmark" } }), _vm._v(" " + _vm._s(_vm.summary.bookmarks) + " ")], 1)])])])], 1)]);
  };
  var _sfc_staticRenderFns$4 = [];
  _sfc_render$4._withStripped = true;
  var __component__$4 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$4,
    _sfc_render$4,
    _sfc_staticRenderFns$4
  );
  __component__$4.options.__file = "/Users/mauricerenck/Sites/kirby-plugins/indieConnector2/src/components/DetailsByMonth.vue";
  const DetailsByMonth = __component__$4.exports;
  const _sfc_main$3 = {
    props: {
      targets: Object
    }
  };
  var _sfc_render$3 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "wrapper" }, [_c("k-headline", { attrs: { "size": "large" } }, [_vm._v("Targets")]), _c("k-line-field"), _c("table", _vm._l(_vm.targets, function(target) {
      return _c("tr", { key: target.id }, [_c("td", [_c("k-link", { attrs: { "to": target.panelUrl, "title": target.slug } }, [_vm._v(_vm._s(target.title))])], 1), _c("td", [_c("div", { class: { dimmed: target.likes === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-fav" } }), _vm._v(" " + _vm._s(target.likes) + " ")], 1)]), _c("td", [_c("div", { class: { dimmed: target.replies === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-reply" } }), _vm._v(" " + _vm._s(target.replies) + " ")], 1)]), _c("td", [_c("div", { class: { dimmed: target.reposts === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-repost" } }), _vm._v(" " + _vm._s(target.reposts) + " ")], 1)]), _c("td", [_c("div", { class: { dimmed: target.mentions === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-mention" } }), _vm._v(" " + _vm._s(target.mentions) + " ")], 1)]), _c("td", [_c("div", { class: { dimmed: target.bookmarks === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-bookmark" } }), _vm._v(" " + _vm._s(target.bookmarks) + " ")], 1)])]);
    }), 0)], 1);
  };
  var _sfc_staticRenderFns$3 = [];
  _sfc_render$3._withStripped = true;
  var __component__$3 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$3,
    _sfc_render$3,
    _sfc_staticRenderFns$3
  );
  __component__$3.options.__file = "/Users/mauricerenck/Sites/kirby-plugins/indieConnector2/src/components/Targets.vue";
  const Targets = __component__$3.exports;
  const _sfc_main$2 = {
    props: {
      sources: Object
    },
    data() {
      return {
        sortedSources: []
      };
    },
    created() {
      this.getSources();
    },
    methods: {
      getSources() {
        this.sortedSources = Object.entries(this.sources).sort((a, b) => {
          return a[1].sum < b[1].sum;
        }).reduce(
          (_sortedObj, [k, v]) => ({
            ..._sortedObj,
            [k]: v
          }),
          {}
        );
      }
    }
  };
  var _sfc_render$2 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "wrapper" }, [_c("k-headline", { attrs: { "size": "large" } }, [_vm._v("Sources")]), _c("k-line-field"), _c("table", _vm._l(this.sortedSources, function(source) {
      return _c("tr", { key: source.id }, [_c("td", [_c("k-link", { staticClass: "k-link-centered", attrs: { "to": source.source, "title": source.source } }, [source.image !== null ? _c("img", { staticClass: "avatar", attrs: { "src": source.image } }) : _vm._e(), _c("span", { staticClass: "shortened-link" }, [_vm._v(_vm._s(source.author))])])], 1), _c("td", [_vm._v(" " + _vm._s(source.title) + " ")]), _c("td", [_c("div", { class: { dimmed: source.likes === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-fav" } }), _vm._v(" " + _vm._s(source.likes) + " ")], 1)]), _c("td", [_c("div", { class: { dimmed: source.replies === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-reply" } }), _vm._v(" " + _vm._s(source.replies) + " ")], 1)]), _c("td", [_c("div", { class: { dimmed: source.reposts === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-repost" } }), _vm._v(" " + _vm._s(source.reposts) + " ")], 1)]), _c("td", [_c("div", { class: { dimmed: source.mentions === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-mention" } }), _vm._v(" " + _vm._s(source.mentions) + " ")], 1)]), _c("td", [_c("div", { class: { dimmed: source.bookmarks === 0 } }, [_c("k-icon", { attrs: { "type": "shape-icon-bookmark" } }), _vm._v(" " + _vm._s(source.bookmarks) + " ")], 1)])]);
    }), 0)], 1);
  };
  var _sfc_staticRenderFns$2 = [];
  _sfc_render$2._withStripped = true;
  var __component__$2 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$2,
    _sfc_render$2,
    _sfc_staticRenderFns$2
  );
  __component__$2.options.__file = "/Users/mauricerenck/Sites/kirby-plugins/indieConnector2/src/components/Sources.vue";
  const Sources = __component__$2.exports;
  const _sfc_main$1 = {
    props: {
      outbox: Object
    }
  };
  var _sfc_render$1 = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "wrapper" }, [_c("k-headline", { attrs: { "size": "large" } }, [_vm._v("Sent Webmentions")]), _c("k-line-field"), _c("table", _vm._l(_vm.outbox, function(target) {
      return _c("tr", { key: target.id }, [_c("td", [_c("k-link", { attrs: { "to": target.panelUrl, "title": target.title } }, [_vm._v(_vm._s(target.title))])], 1), _c("td", [_c("div", [_c("k-icon", { attrs: { "type": "shape-icon-bookmark" } }), _vm._v(" " + _vm._s(target.target) + " ")], 1)])]);
    }), 0)], 1);
  };
  var _sfc_staticRenderFns$1 = [];
  _sfc_render$1._withStripped = true;
  var __component__$1 = /* @__PURE__ */ normalizeComponent(
    _sfc_main$1,
    _sfc_render$1,
    _sfc_staticRenderFns$1
  );
  __component__$1.options.__file = "/Users/mauricerenck/Sites/kirby-plugins/indieConnector2/src/components/Sent.vue";
  const Sent = __component__$1.exports;
  const _sfc_main = {
    props: {
      version: Object
    }
  };
  var _sfc_render = function render() {
    var _vm = this, _c = _vm._self._c;
    return _c("div", { staticClass: "version-box" }, [_vm.version.error ? _c("span", { staticClass: "error" }, [_c("k-info-field", { attrs: { "theme": "negative", "label": "Sorry", "text": "The current version could not be fetched." } })], 1) : _vm.version.updateAvailable ? _c("span", { staticClass: "version" }, [_c("span", { staticClass: "update-available" }, [_vm._v("Update to " + _vm._s(_vm.version.latest) + " available")]), _vm._v(" / "), _c("span", [_vm._v("Your installed version is " + _vm._s(_vm.version.local) + " ")])]) : _vm._e()]);
  };
  var _sfc_staticRenderFns = [];
  _sfc_render._withStripped = true;
  var __component__ = /* @__PURE__ */ normalizeComponent(
    _sfc_main,
    _sfc_render,
    _sfc_staticRenderFns
  );
  __component__.options.__file = "/Users/mauricerenck/Sites/kirby-plugins/indieConnector2/src/components/Version.vue";
  const Version = __component__.exports;
  panel.plugin("mauricerenck/indieConnector", {
    components: {
      "k-webmentions-view": View,
      "Targets": Targets,
      "Sources": Sources,
      "DetailsByMonth": DetailsByMonth,
      "Sent": Sent,
      "Version": Version
    },
    "icons": {
      "shape-icon-bookmark": '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-reactroot=""><path fill = "#293449" d = "M18 22L12 16L6 22V3C6 2.45 6.45 2 7 2H17C17.55 2 18 2.45 18 3V22Z"></path></svg>',
      "shape-icon-fav": '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-reactroot=""><path fill="#293449" d="M3.29289 3.29289C3.68342 2.90237 4.31658 2.90237 4.70711 3.29289L6.70711 5.29289C7.09763 5.68342 7.09763 6.31658 6.70711 6.70711C6.31658 7.09763 5.68342 7.09763 5.29289 6.70711L3.29289 4.70711C2.90237 4.31658 2.90237 3.68342 3.29289 3.29289Z" clip-rule="evenodd" fill-rule="evenodd"></path><path fill="#293449" d="M20.7071 3.29289C21.0976 3.68342 21.0976 4.31658 20.7071 4.70711L18.7071 6.70711C18.3166 7.09763 17.6834 7.09763 17.2929 6.70711C16.9024 6.31658 16.9024 5.68342 17.2929 5.29289L19.2929 3.29289C19.6834 2.90237 20.3166 2.90237 20.7071 3.29289Z" clip-rule="evenodd" fill-rule="evenodd" ></path><path fill="#293449" d="M12 2L15 9H22L17 15L19 22L12 18L5 22L7 15L2 9H9L12 2Z"></path><path fill="#293449" d="M19.7056 16.3528C19.9526 15.8588 20.5533 15.6586 21.0473 15.9056L22.4473 16.6056C22.9413 16.8526 23.1415 17.4533 22.8945 17.9472C22.6475 18.4412 22.0468 18.6414 21.5529 18.3945L20.1529 17.6945C19.6589 17.4475 19.4587 16.8468 19.7056 16.3528Z" clip-rule="evenodd" fill-rule="evenodd"></path><path fill="#293449" d="M4.2944 16.3528C4.54139 16.8468 4.34117 17.4475 3.84719 17.6945L2.44719 18.3945C1.95321 18.6414 1.35254 18.4412 1.10555 17.9472C0.858558 17.4533 1.05878 16.8526 1.55276 16.6056L2.95276 15.9056C3.44674 15.6586 4.04741 15.8588 4.2944 16.3528Z" clip-rule="evenodd" fill-rule="evenodd"></path></svg>',
      "shape-icon-mention": '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-reactroot=""><path fill="#293449" d="M12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9ZM7 12C7 9.23858 9.23858 7 12 7C14.7614 7 17 9.23858 17 12C17 14.7614 14.7614 17 12 17C9.23858 17 7 14.7614 7 12Z" clip-rule="evenodd" fill-rule="evenodd" ></path><path fill="#293449" d="M20.9818 11.4403C20.6939 6.75101 16.6454 2.97295 11.9558 2.9999C10.3701 3.00891 8.89626 3.4225 7.60028 4.15149L7.59817 4.15267C4.4127 5.93438 2.43215 9.60364 3.14475 13.6458C3.80937 17.3894 6.89858 20.3711 10.6618 20.91C12.4448 21.1632 14.1248 20.9 15.5934 20.2463C16.0979 20.0217 16.689 20.2487 16.9136 20.7533C17.1382 21.2578 16.9112 21.8489 16.4067 22.0735C14.5956 22.8797 12.5359 23.1965 10.3793 22.89L10.3783 22.8898C5.76167 22.2287 1.99102 18.5908 1.1754 13.9946L1.17522 13.9936C0.308115 9.07635 2.72704 4.58616 6.62087 2.40772C8.20461 1.5171 10.0104 1.01093 11.9443 0.999932M20.9818 11.4403C21.0175 12.0488 20.9913 12.6539 20.9187 13.2478C20.7765 14.2196 19.9203 14.9499 18.93 14.9499H18.8117C17.8017 14.902 17 14.0617 17 13.0399V7.99992C17 7.44763 16.5523 6.99992 16 6.99992C15.4477 6.99992 15 7.44763 15 7.99992V13.0399C15 15.1323 16.6497 16.8683 18.7516 16.9492C18.7644 16.9497 18.7772 16.9499 18.79 16.9499H18.93C20.8969 16.9499 22.6186 15.5044 22.9001 13.5204L22.9025 13.5021C22.989 12.7997 23.0223 12.0688 22.9783 11.3212L22.9781 11.3187C22.6256 5.56842 17.6944 0.966921 11.9443 0.999932" clip-rule="evenodd" fill-rule="evenodd"></path></svg>',
      "shape-icon-reply": '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-reactroot=""><path fill="#293449" d="M22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22C13.8 22 15.5 21.5 17 20.6L22 22L20.7 17C21.5 15.5 22 13.8 22 12Z"></path><path stroke-linecap="round" stroke-miterlimit="10" stroke-width="1" stroke="white" d="M16.9951 12H17.0051"></path><path stroke-linecap="round" stroke-miterlimit="10" stroke-width="1" stroke="white" d="M11.9951 12H12.0051"></path><path stroke-linecap="round" stroke-miterlimit="10" stroke-width="1" stroke="white" d="M6.99512 12H7.00512"></path><path stroke-linejoin="round" stroke-linecap="round" stroke-miterlimit="10" stroke-width="1" stroke="white" d="M17 12.5C17.2761 12.5 17.5 12.2761 17.5 12C17.5 11.7239 17.2761 11.5 17 11.5C16.7239 11.5 16.5 11.7239 16.5 12C16.5 12.2761 16.7239 12.5 17 12.5Z"></path><path stroke-linejoin="round" stroke-linecap="round" stroke-miterlimit="10" stroke-width="1" stroke="white" d="M12 12.5C12.2761 12.5 12.5 12.2761 12.5 12C12.5 11.7239 12.2761 11.5 12 11.5C11.7239 11.5 11.5 11.7239 11.5 12C11.5 12.2761 11.7239 12.5 12 12.5Z"></path><path stroke-linejoin="round" stroke-linecap="round" stroke-miterlimit="10" stroke-width="1" stroke="white" d="M7 12.5C7.27614 12.5 7.5 12.2761 7.5 12C7.5 11.7239 7.27614 11.5 7 11.5C6.72386 11.5 6.5 11.7239 6.5 12C6.5 12.2761 6.72386 12.5 7 12.5Z"></path></svg>',
      "shape-icon-repost": '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-reactroot=""><path fill="#293449" d="M5 10C3.85228 10 3 10.8523 3 12C3 13.1477 3.85228 14 5 14H6C6.55228 14 7 14.4477 7 15C7 15.5523 6.55228 16 6 16H5C2.74772 16 1 14.2523 1 12C1 9.74772 2.74772 8 5 8H12C12.5523 8 13 8.44772 13 9C13 9.55228 12.5523 10 12 10H5Z" clip-rule="evenodd" fill-rule="evenodd"></path><path fill="#293449" d="M8.99998 5.9458V12.0543L13.8868 9.00005L8.99998 5.9458Z" clip-rule="evenodd" fill-rule="evenodd"></path><path fill="#293449" d="M17 9C17 8.44772 17.4477 8 18 8H19C21.2523 8 23 9.74772 23 12C23 14.2523 21.2523 16 19 16H12C11.4477 16 11 15.5523 11 15C11 14.4477 11.4477 14 12 14H19C20.1477 14 21 13.1477 21 12C21 10.8523 20.1477 10 19 10H18C17.4477 10 17 9.55228 17 9Z" clip-rule="evenodd" fill-rule="evenodd"></path><path fill="#293449" d="M15 18.0542L15 11.9457L10.1132 15L15 18.0542Z" clip-rule="evenodd" fill-rule="evenodd"></path></svg>'
    }
  });
})();
