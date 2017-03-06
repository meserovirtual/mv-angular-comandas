(function () {
    'use strict';

    angular.module('mvMonitor', [])
        .component('mvMonitor', mvMonitor());

    function mvMonitor() {
        return {
            bindings: {
                searchFunction: '&'
            },
            templateUrl: window.installPath + '/mv-angular-comandas/mv-monitor.html',
            controller: MvMonitorController
        }
    }

    MvMonitorController.$inject = ["ComandasService", "ComandasVars", "MvUtils"];
    /**
     * @param mvMonitor
     * @constructor
     */
    function MvMonitorController(ComandasService, ComandasVars, MvUtils) {
        var vm = this;

        vm.comandas = [];
        vm.comanda = {};



        ComandasService.get().then(function (data) {
            console.log(data);
            vm.comandas = data;
        }).catch(function(data){
            console.log(data);
        });




        // Implementación de la paginación
        vm.start = 0;
        vm.limit = ComandasVars.paginacion;
        vm.pagina = ComandasVars.pagina;
        vm.paginas = ComandasVars.paginas;

        function paginar(vars) {
            if (vars == {}) {
                return;
            }
            vm.start = vars.start;
            vm.pagina = vars.pagina;
        }

        vm.next = function () {
            paginar(MvUtils.next(ComandasVars));
        };
        vm.prev = function () {
            paginar(MvUtils.prev(ComandasVars));
        };
        vm.first = function () {
            paginar(MvUtils.first(ComandasVars));
        };
        vm.last = function () {
            paginar(MvUtils.last(ComandasVars));
        };

        vm.goToPagina = function () {
            paginar(MvUtils.goToPagina(vm.pagina, ComandasVars));
        }

    }


})();
