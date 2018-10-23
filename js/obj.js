var obj = {
    isArray : function(obj) {return obj.constructor == Array;},
    inArray : function(arr, obj) {arr.indexOf(obj) != -1;},
    arrayKey : function(arr, obj) {return arr.indexOf(obj);},
    isObject : function(obj) {return obj.constructor==Object;},
    inHash : function(hash, obj) {return hash[obj]!==undefined;},

    setArgDefVal : function(arg, defVal) {return typeof(arg) != "undefined" ? arg : defVal;},
    loop : function(arr, func) {
        for (var i = 0; i < arr.length; i++) {
            func(arr[i]);
        }
    },
};
