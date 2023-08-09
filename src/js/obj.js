/**
 * Набор методов-шорткатов
 *
 * @constructor
 * @this  {obj}
 */
const obj = (function() {
    return {
        isArray : function(obj) {return obj.constructor === Array},
        isObject : function(obj) {return obj.constructor === Object},
        inArray : function(arr, elem) {arr.indexOf(elem) != -1},
        inObject : function(obj, elem) {return obj[elem] !== undefined},
        arrayKey : function(arr, elem) {return arr.indexOf(elem)},
    };
})();

export default obj;
