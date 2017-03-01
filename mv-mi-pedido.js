(function () {
    'use strict';

    angular.module('mvMiPedido', [])
        .component('mvMiPedido', mvMiPedido());

    function mvMiPedido() {
        return {
            bindings: {
                searchFunction: '&'
            },
            templateUrl: window.installPath + '/mv-angular-comandas/mv-mi-pedido.html',
            controller: MvMiPedidoController
        }
    }

    MvMiPedidoController.$inject = ['ComandasService', 'UserService'];
    /**
     * @param MvMiPedido
     * @constructor
     */
    function MvMiPedidoController(ComandasService, UserService) {
        var vm = this;

        vm.comandas = [];
        vm.comanda = {};

        vm.quitar = quitar;

        ComandasService.getByMesa(UserService.getDataFromToken('mesa_id') , UserService.getDataFromToken('session_id')).then(
            function (data) {
                console.log(data);
                vm.comanda = data;
            }
        );


        function quitar(comanda_detalle_id){
            ComandasService.quitar(comanda_detalle_id).then(function(data){
                ComandasService.getByMesa(UserService.getDataFromToken('mesa_id') , UserService.getDataFromToken('session_id')).then(
                    function (data) {
                        console.log(data);
                        vm.comanda = data;
                    }
                );
            })
        }


    }



})();
