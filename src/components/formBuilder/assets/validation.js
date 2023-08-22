/**
 * Компонент валидации
 *
 * @constructor
 * @this  {validation}
 */

import { obj } from 'imnd-obj';

const validation = (function () {
    const checkRules = {
        minlength3: {
            rule: '[а-яёА-ЯЁa-zA-Z0-9_-]{3,}',
            msg: 'должно быть не менее 3 символов',
        },
        minlength6: {
            rule: '[а-яёА-ЯЁa-zA-Z0-9_-]{6,}',
            msg: 'должно быть не менее 6 символов',
        },
        password: {
            rule: '^[a-z0-9]+$|i',
            msg: 'содержит недопустимые символы',
        },
        alpha: {
            rule: '^[а-яёa-z]+$|i',
            msg: '— текстовое поле',
        },
        alphaExt: {
            rule: '^[а-яёa-z- ]+$|i',
            msg: '— текстовое поле',
        },
        numeric: {
            rule: '\d+',
            msg: '— числовое поле',
        },
        telephone: {
            rule: '(\d{3,5})\d{5,7}',
            msg: 'неправильно заполнено',
        },
        fax: {
            rule: '\d{5,7}',
            msg: 'неправильно заполнено',
        },
        mobile: {
            rule: '\d{10,11}',
            msg: 'неправильно заполнено',
        },
        email: {
            rule: '([a-z0-9_\.-]{1,20})@([a-z0-9\.-]{1,20}).([a-z]{2,4})|i',
            msg: 'неправильно заполнено',
        },
    };

    let
        msgContainer = null,
        data = {},
        metadata = {},
        msgContainerId = 'errors_list',

    /**
     * проверяем поля на валидность
     */
    validateData = () => {
        let invalidFields = [],
            field = {},
            fieldData = '',
            fieldMetadata = {};

        for (let dataKey in data) {
            fieldData = data[dataKey];
            fieldMetadata = metadata[dataKey];
            for (let checkItmKey in fieldMetadata.check) {
                let checkItm = fieldMetadata.check[checkItmKey];
                // обязательные для заполненнения поля
                if (checkItm === 'required') {
                    if (
                           fieldMetadata.type === 'checkbox' && fieldData === false
                        || (
                            (fieldMetadata.type === 'input' || 'text' || 'textarea')
                            && (fieldData === '' || fieldData === undefined)
                        )
                    ) {
                        invalidFields.push({
                            field: fieldMetadata.name,
                            message: 'поле "' + fieldMetadata.title + '" обязательно для заполнения.'
                        }); // TODO: закинуть сообщение в конфиг
                    }
                } else if (obj.isObject(checkItm)) {
                    // сравнение полей на идентичность
                    if (compareField === checkItm.compare) {
                        if (fieldData !== data[compareField]) {
                            invalidFields.push({
                                field: fieldMetadata.name,
                                message: 'поле "' + fieldMetadata.title + '" не совпадает с ' + 'полем "' + metadata[compareField]['title']
                            });
                        }
                    }
                } else {
                    // проверяем по остальным критериям
                    for (let checkKey in this.checkRules) {
                        let checkRule = this.checkRules[checkKey];
                        if (checkItm !== checkKey)
                            continue;
                        this.checkData(dataKey, checkKey, checkItm, checkRule['rule'], checkRule['msg'], invalidFields);
                    }
                }
            }
        }

        return invalidFields;
    },

    /**
     * проверяем поле на одно правило валидации
     */
    checkData = function (dataKey, checkKey, checkItm, rule, errMsg, invalidFields) {
        let ruleArr = rule.split('|'),
            pattern = ruleArr[0],
            flags = ruleArr[1],
            regExpr;

        if (flags) {
            regExpr = new RegExp(pattern, flags);
        } else {
            regExpr = new RegExp(pattern);
        }

        if (!regExpr.test(data[dataKey])) {
            let fieldMetadata = metadata[dataKey];
            invalidFields.push({
                field: dataKey,
                message: 'поле "' + fieldMetadata.title + '" ' + errMsg
            });
        }
    },

    setData = function (fields) {
        data = {};
        metadata = {};

        for (let key in fields) {
            let field = fields[key],
                fieldName = field.name;

            metadata[fieldName] = field;
            let data = dom.val(fieldName);
            if (data !== undefined) {
                data[fieldName] = data;
            }
        }
    },

    /**
     * отображение сообщения
     */
    showMessage = function (messageData, type) {
        let i, message = '';
        msgContainer = dom.findById(this.msgContainerId);
        if (msgContainer === null) {
            if (type === 'error')
                message += 'Внимание! ';

            if (obj.isArray(messageData)) {
                for (i = 0; i < messageData.length; i++) {
                    message += messageData[i].message + '; ';
                }
            } else {
                message += messageData;
            }

            alert(message);
            return;
        }

        if (type === 'error') {
            message += '<b>Внимание</b>';
        }

        if (obj.isArray(messageData)) {
            message += '<ul>';
            for (i = 0; i < messageData.length; i++) {
                message += '<li>' + messageData[i].message + '</li>';
            }

            message += '</ul>';
        } else if (obj.isObject(messageData)) {
            message += '<ul>';
            for (let key in messageData) {
                message += '<li>' + key + ": " + messageData[key] + '</li>';
            }

            message += '</ul>';
        } else {
            message += messageData;
        }

        msgContainer.className = type;
        msgContainer.innerHTML = '<div class="' + type + '">' + message + '</div>';
    };

    return {
        run: (fields) => {
            msgContainer = dom.findById(msgContainerId);
            msgContainer.innerHTML = '';
            this.setData(fields);
            let invalidFields = this.validateData();
            if (invalidFields.length > 0) {
                this.showMessage(invalidFields, 'error');
                return false;
            }
            return true;
        },
    };
})();
