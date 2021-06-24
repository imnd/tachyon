/**
 * Набор методов для работы с DOM
 *
 * @constructor
 * @this {dom}
 */
const dom = (function () {
    return {
        obj: null,

        ready: function (a) {
            let b = document;
            let c = "addEventListener";
            b[c] ? b[c]("DOMContentLoaded", a) : window.attachEvent("onload", a)
        },
        click: function (obj, func) {
            obj.addEventListener("click", func);
        },
        blur: function (obj, func) {
            obj.addEventListener("blur", func);
        },

        find: function (string, doc) {
            if (doc === undefined) {
                doc = document;
            }
            return doc.querySelector(string);
        },
        findLast: function (string, doc) {
            if (doc === undefined) {
                doc = document;
            }
            var els = doc.querySelectorAll(string);
            return els[els.length - 1];
        },
        findAll: function (string, doc) {
            if (doc === undefined) {
                doc = document;
            }
            return doc.querySelectorAll(string);
        },

        findObj: function (obj, doc) {
            return typeof (obj) === "object" ? obj : this.findById(obj, doc) || this.findByName(obj, doc);
        },
        findById: function (id, doc) {
            if (doc === undefined) {
                doc = document;
            }
            return doc.getElementById ? doc.getElementById(id) : doc.all ? doc.all[id][1] : doc.layers[id];
        },
        findByTag: function (name, doc) {
            return this.findAllByTag(name, doc)[0];
        },
        findAllByTag: function (name, doc) {
            if (doc === undefined) {
                doc = document;
            }
            if (doc.getElementsByTagName) {
                return doc.getElementsByTagName(name);
            }
        },
        findByName: function (name, doc) {
            if (doc === undefined) {
                doc = document;
            }
            return doc.getElementsByName ? doc.getElementsByName(name)[0] : doc.all ? doc.all[name] : doc.layers[name];
        },
        findAllByName: function (name, doc) {
            if (doc === undefined) {
                doc = document;
            }
            return doc.getElementsByName ? doc.getElementsByName(name) : doc.all ? doc.all[name] : doc.layers[name];
        },
        findByClass: function (className, doc) {
            var objs = this.findAllByClass(className, doc);
            if (objs !== undefined) {
                return objs[0];
            }
        },
        findLastByClass: function (className, doc) {
            var objs = this.findAllByClass(className, doc);
            if (objs !== undefined) {
                return objs[objs.length - 1];
            }
        },
        findAllByClass: function (className, doc) {
            if (doc === undefined) {
                doc = document;
            }
            if (doc.getElementsByClassName(className)) {
                return doc.getElementsByClassName(className);
            }
        },

        create: function (string) {
            var div = document.createElement("div");
            div.innerHTML = string.trim();
            return div.firstChild;
        },
        replace: function (object, string) {
            const newItem = this.create(string);
            object.parentNode.replaceChild(newItem, object);
        },
        html: function (object, html) {
            var obj = this.findObj(object);
            if (obj === null || obj === undefined) {
                return "";
            }
            if (html === undefined) {
                return obj.innerHTML
            }
            obj.innerHTML = html;
        },
        val: function (object, value) {
            var obj = this.findObj(object);
            if (obj === null || obj === undefined) {
                return "";
            }
            const objType = obj.type;
            if (objType === "checkbox") {
                if (value === undefined) {
                    return obj.checked;
                } else {
                    obj.checked = value;
                }
            } else if (
                   objType === "select-one"
                || objType === "select-multiple"
            ) {
                if (value === undefined) {
                    return obj.options[obj.selectedIndex].value ? obj.options[obj.selectedIndex].value : obj.options[obj.selectedIndex].text;
                } else {
                    var options = obj.options;
                    for (var key in options) {
                        if (options[key].value === value) {
                            obj.selectedIndex = key;
                        }
                    }
                }
            } else if (obj.value !== undefined) {
                if (
                       objType === "text"
                    || objType === "password"
                    || objType === "hidden"
                    || objType === "select-one"
                ) {
                    if (value === undefined) {
                        return obj.value;
                    } else {
                        obj.value = value;
                    }
                }
                if (
                       objType === "textarea"
                    || objType === "submit"
                ) {
                    if (value === undefined) {
                        return obj.innerHTML;
                    } else {
                        obj.innerHTML = value;
                    }
                }
            } else if (obj.innerHTML !== undefined) {
                if (value === undefined) {
                    return obj.innerHTML;
                } else {
                    obj.innerHTML = value;
                }
            }
        },
        attr: function (obj, attr, value) {
            obj = this.findObj(obj);
            if (value === undefined) {
                return this.getAttr(obj, attr);
            } else {
                this.setAttr(obj, attr, value);
            }
        },
        getAttr: function (obj, attr) {
            if (obj.getAttribute) {
                return obj.getAttribute(attr);
            }
        },
        setAttr: function (obj, attr, value) {
            if (obj.setAttribute) {
                obj.setAttribute(attr, value);
            }
        },
        /**
         * @param {*} obj
         * @param {string} value
         */
        addClass: function (obj, value) {
            let cls = this.getAttr(obj, "class");
            this.setAttr(obj, "class", cls + " " + value)
        },
        /**
         * @param {*} obj
         * @param {string} value
         */
        removeClass: function (obj, value) {
            let cls = this.getAttr(obj, "class");
            if (cls === '') {
                return;
            }
            let clsArr = cls.split(" ");
            for (let i in clsArr) {
                if (clsArr[i]===value) {
                    clsArr.splice(i,1);
                }
            }
            this.setAttr(obj, "class", clsArr.join(" "))
        },
        clearForm: function () {
            const frm = this.findObj(arguments[0]);
            const ctrls = frm.childNodes;
            for (var i in ctrls) {
                this.clear(ctrls[i]);
            }
        },
        clear: function (object) {
            var obj = this.findObj(object);
            if (obj === undefined) {
                return;
            }
            const objType = obj.type;
            if (objType === undefined) {
                return;
            }
            if (objType === "checkbox") {
                obj.checked = "";
            } else if (objType === "select-one" || objType === "select-multiple") {
                obj.selectedIndex = 0;
            } else if (obj.value !== undefined) {
                if (
                       objType === "text"
                    || objType === "password"
                    || objType === "hidden"
                    || objType === "textarea"
                    || objType === "select-one"
                ) {
                    obj.value = "";
                }
            } else if (obj.innerHTML) {
                obj.innerHTML = "";
            }
        },
        hide: function (objID) {
            var obj = this.findById(objID);
            obj.className = "hidden";
        },

        renderTemplate: function (template, vars) {
            let openCurlPos = template.indexOf("{{"), closeCurlPos;
            while (openCurlPos !== -1) {
                closeCurlPos = template.indexOf("}}");
                let varName = template.substr(openCurlPos + 2, closeCurlPos - openCurlPos - 2).trim();
                prev = template.substr(0, openCurlPos);
                post = template.substr(closeCurlPos + 2);
                template = template.substr(0, openCurlPos) + vars[varName] + template.substr(closeCurlPos + 2);

                openCurlPos = template.indexOf("{{");
            }
            return template;
        }
    };
})();
