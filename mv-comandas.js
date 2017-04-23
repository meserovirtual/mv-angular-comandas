(function () {
    'use strict';

    var scripts = document.getElementsByTagName("script");
    var currentScriptPath = scripts[scripts.length - 1].src;

    if (currentScriptPath.length == 0) {
        currentScriptPath = window.installPath + '/mv-angular-comandas/includes/mv-comandas.php';
    }

    angular.module('mvComandas', [])
        .factory('ComandasService', ComandasService)
        .service('ComandaService', ComandaService)
        .service('ComandasVars', ComandasVars)
    ;


    ComandasService.$inject = ['$http', 'ComandasVars', '$cacheFactory', 'MvUtils', '$q', 'ErrorHandler', 'MvUtilsGlobals'];
    function ComandasService($http, ComandasVars, $cacheFactory, MvUtils, $q, ErrorHandler, MvUtilsGlobals) {
        //Variables
        var service = {};

        var url = currentScriptPath.replace('ac-comanda.js', '/includes/ac-comanda.php');

        //Function declarations
        service.get = get;
        service.getByMesa = getByMesa;
        service.getByParams = getByParams;
        service.getMasVendidos = getMasVendidos;
        service.getByCategoria = getByCategoria;

        service.create = create;
        service.pedir = pedir;
        service.cancelar = cancelar;
        service.servir = servir;
        service.cerrar = cerrar;
        service.quitar = quitar;
        service.pedirPlato = pedirPlato;
        service.getComandaNoEntregadas = getComandaNoEntregadas;
        service.confirmarElaboracion = confirmarElaboracion;
        service.updateStatusComanda = updateStatusComanda;

        service.update = update;

        service.remove = remove;
        service.save = save;

        return service;

        //Functions


        function pedirPlato(comanda_detalle_id) {
            return $http.post(url,
                {function: 'updatePlato', 'comanda_id': comanda_id, 'productos': productos})
                .then(function (response) {
                    ComandasVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    ComandasVars.clearCache = true;
                    ErrorHandler(response.data)
                })
        }

        function quitar(comanda_detalle_id) {
            return $http.post(url,
                {function: 'quitar', 'comanda_detalle_id': comanda_detalle_id})
                .then(function (response) {
                    ComandasVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    ComandasVars.clearCache = true;
                    ErrorHandler(response.data)
                })
        }


        function confirmarElaboracion(detalle) {
            return $http.post(url,
                {function: 'updateStatusPlato', 'detalle': JSON.stringify(detalle)})
                .then(function (response) {
                    ComandasVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    ComandasVars.clearCache = true;
                    ErrorHandler(response.data)
                })
        }

        function updateStatusComanda(comanda) {
            return $http.post(url,
                {function: 'updateStatusComanda', 'comanda': JSON.stringify(comanda)})
                .then(function (response) {
                    ComandasVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    ComandasVars.clearCache = true;
                    ErrorHandler(response.data)
                })
        }

        function pedir(comanda_id) {
            return $http.post(url,
                {function: 'updatePedido', 'comanda_id': comanda_id, 'status': 2})
                .then(function (response) {
                    ComandasVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    ComandasVars.clearCache = true;
                    ErrorHandler(response.data)
                })
        }


        function cancelar(comanda_id) {
            return $http.post(url,
                {function: 'updatePedido', 'comanda_id': comanda_id, 'status': 4})
                .then(function (response) {
                    ComandasVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    ComandasVars.clearCache = true;
                    ErrorHandler(response.data)
                })
        }

        function servir(comanda_id) {
            return $http.post(url,
                {function: 'updatePedido', 'comanda_id': comanda_id, 'status': 3})
                .then(function (response) {
                    ComandasVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    ComandasVars.clearCache = true;
                    ErrorHandler(response.data)
                })
        }


        function cerrar(comanda_id) {
            return $http.post(url,
                {function: 'updatePedido', 'comanda_id': comanda_id, 'status': 5})
                .then(function (response) {
                    ComandasVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    ComandasVars.clearCache = true;
                    ErrorHandler(response.data)
                })
        }

        function getComandaNoEntregadas(comanda) {
            return $http.post(url,
                {function: 'getComandaNoEntregadas', 'comanda': comanda})
                .then(function (response) {
                    ComandasVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    ComandasVars.clearCache = true;
                    ErrorHandler(response.data)
                });
        }

        /**
         * Función que determina si es un update o un create
         * @param comanda
         * @returns {*}
         */
        function save(comanda) {

            var deferred = $q.defer();

            if (comanda.comanda_id != undefined) {
                deferred.resolve(update(comanda));
            } else {
                deferred.resolve(create(comanda));
            }
            return deferred.promise;
        }

        /**
         * @description Obtiene todos los comanda
         * @param callback
         * @returns {*}
         */
        function get() {
            MvUtilsGlobals.startWaiting();
            var urlGet = url + '?function=getComanda';
            var $httpDefaultCache = $cacheFactory.get('$http');
            var cachedData = [];


            // Verifica si existe el cache de comanda
            if ($httpDefaultCache.get(urlGet) != undefined) {
                if (ComandasVars.clearCache) {
                    $httpDefaultCache.remove(urlGet);
                }
                else {
                    var deferred = $q.defer();
                    cachedData = $httpDefaultCache.get(urlGet);
                    deferred.resolve(cachedData);
                    MvUtilsGlobals.stopWaiting();
                    return deferred.promise;
                }
            }


            return $http.get(urlGet, {cache: true})
                .then(function (response) {
                    for (var i = 0; i < response.data.length; i++) {
                        if(response.data[i].precios != undefined) {
                            response.data[i].precios[0].precio = parseFloat(response.data[i].precios[0].precio);
                            response.data[i].precios[1].precio = parseFloat(response.data[i].precios[1].precio);
                            response.data[i].precios[2].precio = parseFloat(response.data[i].precios[2].precio);
                            response.data[i].precios.sort(function (a, b) {
                                // Turn your strings into dates, and then subtract them
                                // to get a value that is either negative, positive, or zero.
                                return a.precio_tipo_id - b.precio_tipo_id;
                            });
                            response.data[i].pto_repo = parseFloat(response.data[i].pto_repo);
                            response.data[i].iva = parseFloat(response.data[i].iva);
                            response.data[i].status = '' + response.data[i].status;
                            response.data[i].en_oferta = '' + response.data[i].en_oferta;
                            response.data[i].en_slider = '' + response.data[i].en_slider;
                            response.data[i].destacado = '' + response.data[i].destacado;
                            response.data[i].comanda_tipo = '' + response.data[i].comanda_tipo;
                        }
                    }

                    $httpDefaultCache.put(urlGet, response.data);
                    ComandasVars.clearCache = false;
                    ComandasVars.paginas = (response.data.length % ComandasVars.paginacion == 0) ? parseInt(response.data.length / ComandasVars.paginacion) : parseInt(response.data.length / ComandasVars.paginacion) + 1;
                    MvUtilsGlobals.stopWaiting();
                    return response.data;
                })
                .catch(function (response) {
                    ComandasVars.clearCache = true;
                    MvUtilsGlobals.stopWaiting();
                    ErrorHandler(response);
                })
        }


        function getByMesa(session_id, mesa_id) {
            return $http.get(url + '?function=getComanda&session_id=' + session_id + '&mesa_id=' + mesa_id)
                .then(function (response) {
                    ComandasVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    ComandasVars.clearCache = true;
                    ErrorHandler(response)
                })
        }

        /**
         * @description Retorna la lista filtrada de comanda
         * @param params -> String, separado por comas (,) que contiene la lista de par�metros de b�squeda, por ej: nombre, sku
         * @param values
         * @param exact_match
         */
        function getByParams(params, values, exact_match) {
            return get().then(function (data) {
                return MvUtils.getByParams(params, values, exact_match, data);
            }).then(function (data) {
                return data;
            });
        }

        /**
         * @description Retorna los primero 8 comanda mas vendidos
         * @param callback
         */
        function getMasVendidos(callback) {
            get(function (data) {
                var response = data.sort(function (a, b) {
                    return b.vendidos - a.vendidos;
                });

                callback(response.slice(0, 8));
            });
        }

        /**
         * @description Retorna un listado de comanda filtrando por la categoria
         * @param categoria_id
         * @param callback
         */
        function getByCategoria(categoria_id, callback) {
            var comanda = [];
            get(function (data) {
                if (data == undefined || data.length == 0)
                    return callback(comanda);

                data.forEach(function (comanda) {
                    if (comanda === undefined || comanda.categorias === undefined || comanda.categorias.length == 0)
                        return callback(comanda);

                    if (categoria_id == comanda.categorias[0].categoria_id)
                        comanda.push(comanda);
                });
                return callback(comanda);
            });
        }

        /** @name: remove
         * @param comanda_id
         * @param callback
         * @description: Elimina el comanda seleccionado.
         */
        function remove(comanda_id, callback) {
            return $http.post(url,
                {function: 'removeComanda', 'comanda_id': comanda_id})
                .success(function (data) {
                    //console.log(data);
                    if (data !== 'false') {
                        ComandasVars.clearCache = true;
                        callback(data);
                    }
                })
                .error(function (data) {
                    callback(data);
                })
        }

        /**
         * @description: Crea un comanda.
         * @param comanda
         * @returns {*}
         */
        function create(comanda) {

            return $http.post(url,
                {
                    'function': 'createComanda',
                    'comanda': JSON.stringify(comanda)
                })
                .then(function (response) {
                    ComandasVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    ComandasVars.clearCache = true;
                    ErrorHandler(response)
                });
        }


        /** @name: update
         * @param comanda
         * @description: Realiza update al comanda.
         */
        function update(comanda) {
            return $http.post(url,
                {
                    'function': 'updateComanda',
                    'comanda': JSON.stringify(comanda)
                })
                .then(function (response) {
                    ComandasVars.clearCache = true;
                    return response.data;
                })
                .catch(function (response) {
                    ComandasVars.clearCache = true;
                    ErrorHandler(response.data)
                });
        }

    }


    ComandaService.$inject = ['$rootScope'];
    function ComandaService($rootScope) {

        this.comanda = {};

        this.broadcast = function () {
            $rootScope.$broadcast("refreshComanda")
        };

        this.listen = function (callback) {
            $rootScope.$on("refreshComanda", callback)
        };
    }


    ComandasVars.$inject = [];
    /**
     * @description Almacena variables temporales de comanda
     * @constructor
     */
    function ComandasVars() {
        // Cantidad de p�ginas total del recordset
        this.paginas = 1;
        // P�gina seleccionada
        this.pagina = 1;
        // Cantidad de registros por p�gina
        this.paginacion = 10;
        // Registro inicial, no es p�gina, es el registro
        this.start = 0;


        // Indica si se debe limpiar el cach� la pr�xima vez que se solicite un get
        this.clearCache = true;

    }


})();