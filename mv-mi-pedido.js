(function () {
    'use strict';

    angular.module('mvMiPedido', [])
        .component('mvMiPedido', mvMiPedido())
        .service('mvMiPedidoService', mvMiPedidoService);

    function mvMiPedido() {
        return {
            bindings: {
                searchFunction: '&'
            },
            templateUrl: window.installPath + '/mv-angular-comandas/mv-mi-pedido.html',
            controller: MvMiPedidoController
        }
    }

    MvMiPedidoController.$inject = ['ComandasService', 'UserService', '$rootScope'];
    /**
     * @param MvMiPedido
     * @constructor
     */
    function MvMiPedidoController(ComandasService, UserService, $rootScope) {
        var vm = this;

        vm.comandas = [];
        vm.comanda = {};

        vm.quitar = quitar;

        $rootScope.$on('miPedidoRefresh', function(){
            ComandasService.getByMesa(UserService.getDataFromToken('mesa_id'), UserService.getDataFromToken('session_id')).then(
                function (data) {
                    vm.comanda = data;
                }
            );
        });

        ComandasService.getByMesa(UserService.getDataFromToken('mesa_id'), UserService.getDataFromToken('session_id')).then(
            function (data) {
                console.log(data);
                vm.comanda = data;
            }
        );


        function quitar(comanda_detalle_id) {
            ComandasService.quitar(comanda_detalle_id).then(function (data) {
                ComandasService.getByMesa(UserService.getDataFromToken('mesa_id'), UserService.getDataFromToken('session_id')).then(
                    function (data) {
                        console.log(data);
                        vm.comanda = data;
                    }
                );
            })
        }


    }

    mvMiPedidoService.$inject = ['$rootScope'];

    function mvMiPedidoService($rootScope) {
        var service = this;
        service.refresh = refresh;

        return service;

        function refresh(){
            $rootScope.$broadcast('miPedidoRefresh')
        }
    }

})();
