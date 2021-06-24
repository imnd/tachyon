/**
 * Набор методов-шорткатов
 *
 * @constructor
 * @this  {obj}
 */
export let obj = (function() {
    return {
        isArray : function(obj) {return obj.constructor === Array},
        inArray : function(arr, obj) {arr.indexOf(obj) !== -1},
        arrayKey : function(arr, obj) {return arr.indexOf(obj)},
        isObject : function(obj) {return obj.constructor===Object},
        inHash : function(hash, obj) {return hash[obj]!==undefined},
        forEach : function(arr, func) {
            for (let i in arr) {
                if (arr.hasOwnProperty(i)) {
                    func(arr[i]);
                }
            }
        },
        loop : function(arr, func) {
            for (let i = 0; i < arr.length; i++) {
                func(arr[i]);
            }
        },
    };
})();

// export default {obj};

