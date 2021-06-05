var HttpClient = function () {
    this.response = null;
    this.interceptors = [];
    this.options = {};
    var $this = this;

    this.request = function (type, url, data, options) {
        this.setOptions(options);
        var resolver = ajax.request(type, url, data, this.options);
        if (this.interceptors.length > 0) {
            var nextHandler = null;
            var handler = null;
            var lastHandler = null;
            var finalResolve = null;
            var finalReject = null;
            var response = {
                success: false,
                content: null,
                canceled: false,
                headers: {},
                status: 0
            };
            var makeRequest = function (after) {
                // console.log('==Request==');
                lastHandler.after = after;
                resolver.then(function (data) {
                    response.success = true;
                    response.content = data;
                    after(lastHandler.next);
                }, function (error) {
                    response.content = error;
                    after(lastHandler.next);
                });
            };
            // var handlers = [];
            for (var i = this.interceptors.length - 1; i >= 0; i--) {
                var interceptor = this.interceptors[i];
                var entryCall = interceptor[0][interceptor[1]];

                handler = {
                    response: response,
                    handle: makeRequest,
                    onHandle: entryCall,
                    httpClient: $this,
                    after: function () {
                        // console.log('empty after');
                    },
                    next: function () {
                        // console.log('next called', this);
                        if (this.previousHandler) {
                            this.previousHandler.after(this.previousHandler.next);
                        } else {
                            // this.after(function () {
                            // console.log('--Resolving data--');
                            if (response.success) {
                                finalResolve(response.content);
                            } else {
                                finalReject(response.content);
                            }
                            // });
                        }
                        // call nextHandler.after(nextHandler.next);
                    }
                };
                if (!lastHandler) {
                    lastHandler = handler;
                }
                // handlers.unshift(handler);
                handler.next = handler.next.bind(handler);
                if (nextHandler) {
                    handler.nextHandler = nextHandler;
                    // (function (nextHandler, handler) {
                    //     nextHandler.handle = handler.onHandle
                    // })(nextHandler, handler);  
                    nextHandler.previousHandler = handler;
                    handler.handle = (function (nextHandler) {
                        return function (after) {
                            nextHandler.previousHandler.after = after;
                            nextHandler.onHandle(nextHandler);
                            // console.log('after', nextHandler);
                        };
                    })(nextHandler);
                }
                nextHandler = handler;
            }
            // console.log(handlers);

            return new OnReady(function (resolve, reject) {
                finalResolve = resolve;
                finalReject = reject;
                handler.onHandle(handler);
            });
            // OLD
            // for (var i = this.interceptors.length - 1; i >= 0; i--) {
            //     var httpMiddleWare = this.interceptors[i];
            //     var nextAction = resolver.action;
            //     resolver = (function (nextAction) {
            //         return new OnReady(function (onOk, onError) {
            //             httpMiddleWare[0][httpMiddleWare[1]]($this,
            //                 // next
            //                 function () {
            //                     nextAction(onOk, onError);
            //                 },
            //                 // onError
            //                 onError
            //             );
            //         })
            //     })(nextAction);
            // }
        }
        return resolver;
    };

    this.get = function (url, options) {
        var resolver = $this.request('GET', url, null, options);
        return resolver;
    };

    this.post = function (url, data, options) {
        var resolver = $this.request('POST', url, data, options);
        return resolver;
    };

    this.put = function (url, data, options) {
        var resolver = $this.request('PUT', url, data, options);
        return resolver;
    };

    this.delete = function (url, data, options) {
        var resolver = $this.request('DELETE', url, data, options);
        return resolver;
    };

    this.with = function (interceptor) {
        var client = new HttpClient();
        client.interceptors = this.interceptors.slice();
        client.interceptors.push(interceptor);
        return client;
    }

    this.setOptions = function (options) {
        for (var k in options) {
            this.options[k] = options[k];
        }
    }
};
