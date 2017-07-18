(function () {
    'use strict';

    angular.module('mvVentasWeb', [])
        .component('mvVentasWeb', mvVentasWeb());

    function mvVentasWeb() {
        return {
            bindings: {
                searchFunction: '&'
            },
            templateUrl: window.installPath + '/mv-angular-comandas/mv-ventas-web.html',
            controller: MvVentasWebController
        }
    }

    MvVentasWebController.$inject = ['ComandasService', 'ComandasVars', "MvUtils"];
    /**
     * @param AcUsuarios
     * @constructor
     */
    function MvVentasWebController(ComandasService, ComandasVars, MvUtils) {
        var vm = this;

        vm.ventas_web = [];
        vm.soloActivos={};
        vm.soloActivos.status = 2;
        vm.comanda = {};

        //FUNCIONES
        //vm.detalle = detalle;
        vm.loadVentasWeb = loadVentasWeb;
        vm.getStatus = getStatus;
        vm.cancel = cancel;


        loadVentasWeb();


        function loadVentasWeb() {
            ComandasService.getPedidosWeb().then(function (data) {
                console.log(data);
                vm.ventas_web = data;
            }).catch(function(error){
                console.log(error);
            });
        }

        function getStatus(status) {
            if(status == 0)
                return "Inicial";
            else if(status == 1)
                return "Creada";
            else if(status == 2)
                return "Pedida";
            else if(status == 3)
                return "Servida";
            else if(status == 4)
                return "Cancelada";
            else if(status == 5)
                return "Cerrada";
        }

        function cancel() {
            vm.detailsOpen = false;
            vm.comanda = {};
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
