/**
 * Компонент для отправки AJAX запросов
 * 
 * @constructor
 * @this  {ajax}
 */
var ajax = (function() {
    return {
        get : function (path, data, callback, respType, contentType) {
            if (typeof data === "function") {
                respType = callback;
                callback = data;
                data = {};
            }
            this.sendRequest({
                path : path,
                data : data,
                callback : callback,
                respType : respType,
                type : "GET",
                contentType : contentType,
            });
        },
        post : function (path, data, callback, respType, contentType) {
            this.sendRequest({
                path : path,
                data : data,
                callback : callback,
                respType : respType,
                type : "POST",
                contentType : contentType,
            });
        },
        sendRequest : function(options) {
            var xhr = this.createRequest();
            if (!xhr) {
                alert("Браузер не поддерживает AJAX");
                return;            
            }
            var
                sendData,
                path = options["path"] + "?ajax=true",
                callback = options["callback"],
                requestType = options["type"],
                data = options["data"] || {},
                respType = options["respType"] || "json",
                contentType = options["contentType"] || "application/x-www-form-urlencoded"
            ;

            if (requestType=="GET") {
                path = this.appendPath(path, data);
            } else if (contentType=="multipart/form-data") {
                var boundary = String(Math.random()).slice(2);
                contentType += '; boundary=' + boundary;
                sendData = '\r\n--' + boundary + '\r\nContent-Disposition: form-data; name="data"; filename="' + data.fileName + '"\r\nContent-Type: ' + data.fileType + '\r\n\r\n' + data.data + '\r\n--' + boundary + '--\r\n';
                delete data.fileName;
                delete data.fileType;
                delete data.data;
                path = this.appendPath(path, data);
            } else {
                sendData = [];
                for (var key in data) {
                    sendData.push(key + "=" + data[key]);
                }
                sendData = sendData.join("&");
            }
            xhr.open(requestType, path, true);
            xhr.setRequestHeader("Content-Type", contentType);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        var rData = xhr.responseText;
                        if (respType==="json") {
                            if (rData=="true") {
                                return {success: true};
                            }
                            if (rData=="false") {
                                return {success: false};
                            }
                            var eData = !(/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(rData.replace(/"(\\.|[^"\\])*"/g, ""))) && eval("(" + rData + ")");
                            var eArray = new Object(eData);
                            callback(eArray);
                        } else {
                            callback(rData);
                        }
                    }
                }
            };
            // Тело запроса готово, отправляем
            xhr.send(sendData);
        },
        appendPath : function(path, data) {
            for (var key in data) {
                path += "&" + key + "=" + data[key];
            }
            return path;
        },
        createRequest : function() {
            if (window.XMLHttpRequest) {
                return new XMLHttpRequest();
            } else if (window.ActiveXObject) {
                var xhr;
                try {
                    xhr = new ActiveXObject("Msxml2.XMLHTTP"); 
                } catch (e){}
                try {
                    xhr = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e){
                    return false;
                }
                return xhr;
            }
            return false;
        },
    };
})();