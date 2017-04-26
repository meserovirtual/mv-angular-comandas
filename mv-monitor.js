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

    MvMonitorController.$inject = ["ComandasService", "$timeout", "$document", "MesasService", "$element",
        "$location", "ComandaService", '$interval'];
    /**
     * @param mvMonitor
     * @constructor
     */
    function MvMonitorController(ComandasService, $timeout, $document, MesasService, $element,
                                 $location, ComandaService, $interval) {
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
        vm.navigate = navigate;


        MesasService.get().then(function (mesas) {
            vm.mesas = mesas;
        }).catch(function (error) {
            console.log(error);
        });

        // loadComandas();


        // NAVEGACIÓN

        vm.comandaSelected = 0;
        vm.comandasAmount = 0;
        vm.columns = 0;
        vm.rows = 0;
        vm.activeDish = -1;
        vm.activeDetalle = '';
        vm.active = false;


        calculateRowsColums();
        vm.el = angular.element(document.querySelector('#mv-monitor-container'));
        vm.el[0].focus();
        $interval(function () {
            calculateRowsColums();
        }, 10000);


        function calculateRowsColums() {
            if (vm.comanda.length == 0) {
                return;
            }
            ComandasService.getByParams("status", "0,1,2,3", "true").then(function (comandas) {
                //console.log(comandas);
                vm.comandas = comandas;
                // var comandas = angular.element(document.querySelector('.container-comanda'));

                $timeout(function () {
                    vm.el[0].focus();
                    var _comandas = vm.el[0].getElementsByClassName('container-comanda');
                    var boundingRect = vm.el[0].getBoundingClientRect();
                    var comandaBoundingRect = _comandas[0].getBoundingClientRect();

                    vm.columns = Math.floor(boundingRect.width / (comandaBoundingRect.width * 1.1));
                    vm.rows = (_comandas.length % vm.columns == 2) ? vm.comandas.length / vm.columns : Math.ceil(_comandas.length / vm.columns);
                }, 0);


            }).catch(function (error) {
                console.log(error);
            });


        }


        function loadComandas() {
            ComandasService.getByParams("status", "0,1,2,3", "true").then(function (comandas) {
                //console.log(comandas);
                vm.comandas = comandas;
                calculateRowsColums();
            }).catch(function (error) {
                console.log(error);
            });
        }

        function getOrigen(origen_id) {
            var origen = '';
            if (origen_id == -1) {
                origen = 'Mostrador';
            } else if (origen_id == -2) {
                origen = 'Delivery';
            } else {
                origen = 'Mesa ' + getMesa(origen_id);
            }

            return origen;
        }

        function getMesa(origen_id) {
            for (var i = 0; i <= vm.mesas.length - 1; i++) {
                if (origen_id == vm.mesas[i].mesa_id) {
                    return vm.mesas[i].mesa;
                }
            }
        }

        function getNroEnvio(comanda) {
            if (comanda.origen_id == -2) {
                return '#' + comanda.envio_id;
            } else if (comanda.origen_id == -1) {
                return '#' + comanda.comanda_id;
            }
        }


        function cancelarPlato(comanda_detalle_id) {
            //console.log(comanda_detalle_id);
            ComandasService.quitar(comanda_detalle_id).then(function (data) {
                //console.log(data);
                loadComandas();
            }).catch(function (error) {
                console.log(error);
            });
        }

        function confirmarElaboracion(detalle) {
            detalle.status = 2;
            detalle.platoStatus = 2;
            detalle.preparacion_fin = new Date();
            //console.log(detalle);
            ComandasService.confirmarElaboracion(detalle).then(function (data) {
                //console.log(data);
                loadComandas();
            }).catch(function (error) {
                console.log(error);
            });
        }

        function showCaja(comanda) {
            //console.log(comanda);
            ComandaService.comanda = comanda;
            //ComandaService.broadcast();
            $location.path('/caja/cobros');
        }


        function navigate(event) {

            // Izquierda
            if (event.keyCode == 37) {
                vm.comandaSelected = (vm.comandaSelected <= 0) ? 0 : vm.comandaSelected - 1;
                return;
            }
            // Arriba
            if (event.keyCode == 38) {
                if (vm.active) {
                    vm.activeDish = (vm.activeDish - 1 < 0) ? 0 : vm.activeDish - 1;
                    vm.activeDetalle = 'detalle-' + vm.comandaSelected + '-' + vm.activeDish;
                } else {
                    vm.comandaSelected = (vm.comandaSelected - vm.columns < 0) ? 0 : vm.comandaSelected - vm.columns;
                }
                scrollPosition();
                return;
                // vm.el[0].scrollTop = angular.element(document.querySelector('#comanda-' + vm.comandaSelected)).getBoundingClientRect().top;
            }
            // Derecha
            if (event.keyCode == 39) {
                vm.comandaSelected = (vm.comandaSelected >= vm.comandas.length) ? vm.comandas.length - 1 : vm.comandaSelected + 1;
                return;
            }
            // Abajo
            if (event.keyCode == 40) {
                if (vm.active) {
                    vm.activeDish = (vm.activeDish + 1 > Object.getOwnPropertyNames(vm.comandas[vm.comandaSelected].detalles).length - 1) ? Object.getOwnPropertyNames(vm.comandas[vm.comandaSelected].detalles).length - 1 : vm.activeDish + 1;
                    vm.activeDetalle = 'detalle-' + vm.comandaSelected + '-' + vm.activeDish;
                } else {
                    vm.comandaSelected = (vm.comandaSelected + vm.columns > vm.comandas.length - 1) ? vm.comandas.length - 1 : vm.comandaSelected + vm.columns;
                }
                scrollPosition();
                return;
            }

            // +
            if (event.keyCode == 107) {
                vm.active = true;
                vm.activeDish = 0;
                vm.activeDetalle = 'detalle-' + vm.comandaSelected + '-' + vm.activeDish;
                return;
            }

            // - o esc
            if (event.keyCode == 109 || event.keyCode == 27) {
                vm.active = false;
                vm.activeDish = -1;
                vm.activeDetalle = '';
                return;
            }

            // Enter
            if (event.keyCode == 13) {
                if (vm.active) {
                    confirmarElaboracion(vm.comandas[vm.comandaSelected].detalles[Object.getOwnPropertyNames(vm.comandas[vm.comandaSelected].detalles)[vm.activeDish]]);
                } else {
                    showCaja(vm.comandas[vm.comandaSelected]);
                }
                return;

            }

            // / - Cancelar Pedido
            if (event.keyCode == 11) {
                if (vm.active) {
                    cancelarPlato(vm.comandas[vm.comandaSelected].detalles[Object.getOwnPropertyNames(vm.comandas[vm.comandaSelected].detalles)[vm.activeDish]].comanda_detalle_id);
                }
                return;
            }

        }

        function scrollPosition() {
            var row = Math.ceil((vm.comandaSelected + 1) / vm.columns);
            var steps = $document[0].body.scrollHeight / vm.rows;
            $document[0].body.scrollTop = steps * (row - 1);
        }


    }


})();
