(function () {
    'use strict';

    angular.module('acProductosAdministracion', [])
        .component('acProductosAdministracion', acProductosAdministracion());

    function acProductosAdministracion() {
        return {
            bindings: {
                searchFunction: '&'
            },
            templateUrl: window.installPath + '/ac-angular-productos/ac-productos-administracion.html',
            controller: AcProductosController
        }
    }

    AcProductosController.$inject = ["ProductVars", 'ProductService', "AcUtils", "CategoryService", "UserService", "$scope", "UploadVars", "UploadService"];
    /**
     * @param AcProductos
     * @constructor
     */
    function AcProductosController(ProductVars, ProductService, AcUtils, CategoryService, UserService, $scope, UploadVars, UploadService) {
        var vm = this;

        vm.productos = [];
        vm.producto = {};
        vm.listProveedores = [];
        vm.producto_para_kit = {};
        vm.productos_en_kit = [];


        vm.foto_01 = {nombre: 'no_image.png'};
        vm.foto_02 = {nombre: 'no_image.png'};
        vm.foto_03 = {nombre: 'no_image.png'};
        vm.foto_04 = {nombre: 'no_image.png'};


        vm.save = save;
        vm.setData = setData;
        vm.agregarKit = agregarKit;
        vm.quitarKit = quitarKit;
        vm.searchProductoKit = searchProductoKit;
        vm.updateFotoProyecto = updateFotoProyecto;


        function updateFotoProyecto(filelist, id, sub_folder) {
            UploadService.addImages(filelist, id, sub_folder, function (data) {
                for (var i = 0; i < UploadVars.uploadsList.length; i++) {
                    if (UploadVars.uploadsList[i].id == 1) {
                        vm.foto_01.nombre = UploadVars.uploadsList[i].file.name;
                    }
                    if (UploadVars.uploadsList[i].id == 2) {
                        vm.foto_02.nombre = UploadVars.uploadsList[i].file.name;
                    }
                    if (UploadVars.uploadsList[i].id == 3) {
                        vm.foto_03.nombre = UploadVars.uploadsList[i].file.name;
                    }
                    if (UploadVars.uploadsList[i].id == 4) {
                        vm.foto_04.nombre = UploadVars.uploadsList[i].file.name;
                    }

                }
                $scope.$apply();
                //console.log(data);
            })
        }


        $scope.$watch('$ctrl.producto', function () {
            vm.listProveedores = [];
            if (vm.producto.proveedores == undefined) {
                return;
            }

            for (var i in vm.producto.proveedores) {
                vm.listProveedores[vm.producto.proveedores[i].usuario_id] = true;
            }
        });


        function save() {

            vm.producto.proveedores = [];

            for (var prop in vm.listProveedores) {
                if (vm.listProveedores[prop]) {
                    vm.producto.proveedores.push({proveedor_id: prop});
                }
            }

            vm.producto.fotos = [];
            vm.producto.fotos[0] = vm.foto_01;
            vm.producto.fotos[1] = vm.foto_02;
            vm.producto.fotos[2] = vm.foto_03;
            vm.producto.fotos[3] = vm.foto_04;


            ProductService.save(vm.producto).then(function (data) {

                return ProductService.get();
            }).then(function (data) {
                setData(data);
            });

        }

        function setData(data) {
            vm.productos = data;
            vm.paginas = ProductVars.paginas;
        }


        CategoryService.get().then(function (data) {
            vm.categorias = data;
        });

        ProductService.get().then(function (data) {
            setData(data);
        });

        UserService.getByParams('rol_id', '2', 'true').then(function (data) {
            vm.proveedores = data;
        });

        function searchProductoKit(callback) {
            ProductService.get().then(callback);
        }

        function agregarKit() {
            vm.producto.kits.push({
                producto_cantidad: 1,
                producto_id: vm.producto_para_kit.producto_id,
                nombre: vm.producto_para_kit.nombre
            });

            vm.producto_para_kit = {};
        }


        function quitarKit(producto_kit) {
            for (var i = 0; i < vm.producto.kits.length; i++) {
                if (producto_kit.producto_id == vm.producto.kits[i].producto_id) {

                    vm.producto.kits.splice(i, 1);
                }
            }
            //vm.productos_en_kit(producto);
        }

        // Implementación de la paginación
        vm.start = 0;
        vm.limit = ProductVars.paginacion;
        vm.pagina = ProductVars.pagina;
        vm.paginas = ProductVars.paginas;

        function paginar(vars) {
            if (vars == {}) {
                return;
            }
            vm.start = vars.start;
            vm.pagina = vars.pagina;
        }

        vm.next = function () {
            paginar(AcUtils.next(ProductVars));
        };
        vm.prev = function () {
            paginar(AcUtils.prev(ProductVars));
        };
        vm.first = function () {
            paginar(AcUtils.first(ProductVars));
        };
        vm.last = function () {
            paginar(AcUtils.last(ProductVars));
        };

        vm.goToPagina = function () {
            paginar(AcUtils.goToPagina(vm.pagina, ProductVars));
        }

    }


})();
