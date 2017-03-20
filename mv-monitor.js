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

        vm.getOrigen = getOrigen;
        vm.getNroEnvio = getNroEnvio;

        ComandasService.get().then(function (data) {
            console.log(data);
            vm.comandas = data;
        }).catch(function(data){
            console.log(data);
        });

        function getOrigen(origen_id) {
            var origen = '';
            switch (origen_id) {
                case 1:
                    origen = 'Mostrador';
                    break;
                case 2:
                    origen = 'Delivery';
                    break;
                default:
                    origen = 'Mesa';
                    break;
            }
            return origen;
        }

        function getNroEnvio(origen_id) {
            var nroEnvio = '';
            if(origen_id == 2) {
                //TODO: en base al origen_id debería poder saber cual es el envio asociado a la comanda
                //Falta esa tabla relacional
            }
            return nroEnvio;
        }


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
