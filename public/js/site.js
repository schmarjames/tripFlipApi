angular
  .module('travelbloc', ['ui.router', 'ui.bootstrap', 'ngStorage'])
  .config(['$stateProvider', '$httpProvider',
    function config($stateProvider, $httpProvider) {
      console.log("route section");
    }]);


console.log("script file");

//# sourceMappingURL=site.js.map