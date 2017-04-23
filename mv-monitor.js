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

    MvMonitorController.$inject = ["ComandasService", "ComandasVars", "MvUtils", "MesasService", "EnviosService", "$location", "ComandaService"];
    /**
     * @param mvMonitor
     * @constructor
     */
    function MvMonitorController(ComandasService, ComandasVars, MvUtils, MesasService, EnviosService, $location, ComandaService) {
        var vm = this;

        vm.comandas = [];
        vm.comanda = {};
        vm.mesas = [];

        //funciones
        vm.getOrigen = getOrigen;
        vm.getNroEnvio = getNroEnvio;
        vm.cancelarPlato = cancelarPlato;
        vm.confirmarElaboracion = confirmarElaboracion;
        vm.showCaja = showCaja;


        MesasService.get().then(function(mesas){
            vm.mesas = mesas;
        }).catch(function(error){
            console.log(error);
        });

        loadComandas();

        function loadComandas() {
            /*
             ComandasService.get().then(function (comandas) {
             //console.log(comandas);
             vm.comandas = comandas;
             }).catch(function(error){
             console.log(error);
             });
             */
            ComandasService.getByParams("status", "0,1,2,3", "true").then(function (comandas) {
                //console.log(comandas);
                vm.comandas = comandas;
            }).catch(function(error){
                console.log(error);
            });
        }

        function getOrigen(origen_id) {
            var origen = '';
            if(origen_id == -1) {
                origen = 'Mostrador';
            } else if(origen_id == -2) {
                origen = 'Delivery';
            } else {
                origen = 'Mesa ' + getMesa(origen_id);
            }

            return origen;
        }

        function getMesa(origen_id) {
            for(var i=0; i <= vm.mesas.length - 1; i++) {
                if(origen_id == vm.mesas[i].mesa_id) {
                    return vm.mesas[i].mesa;
                }
            }
        }

        function getNroEnvio(comanda) {
            if(comanda.origen_id == -2) {
                return '#' + comanda.envio_id;
            } else if(comanda.origen_id == -1) {
                return '#' + comanda.comanda_id;
            }
        }


        function cancelarPlato(comanda_detalle_id) {
            //console.log(comanda_detalle_id);
            ComandasService.quitar(comanda_detalle_id).then(function(data){
                //console.log(data);
                loadComandas();
            }).catch(function(error){
                console.log(error);
            });
        }

        function confirmarElaboracion(detalle) {
            detalle.status = 2;
            detalle.platoStatus = 2;
            //console.log(detalle);
            ComandasService.confirmarElaboracion(detalle).then(function(data){
                //console.log(data);
                loadComandas();
            }).catch(function(error){
                console.log(error);
            });
        }

        function showCaja(comanda) {
            //console.log(comanda);
            ComandaService.comanda = comanda;
            //ComandaService.broadcast();
            $location.path('/caja/cobros');
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
