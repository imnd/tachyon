/**
 * Datepicker
 *
 * @constructor
 * @this {datepicker}
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */

const datepicker = (function() {
    const styles = '\
        .hidden {display: none;}\
        .form__field {position: relative;display: flex;flex-wrap: wrap;align-items: stretch;width: auto;}\
        .datepicker{position:absolute;z-index:50;margin-top:5px;padding:20px 16px; width:220px;top:100%;left:0;background-color:#fff;border:1px solid #D0D0D0;border-radius:5px;}\
        .datepicker__nav{padding:0 3px;margin-bottom:16px}\
        .datepicker__nav,.datepicker__nav-content{display:flex;align-items:center;justify-content:space-between}\
        .datepicker__nav-content{flex-grow:1;padding:0 15px}\
        .datepicker__nav-action{width:30px;cursor:pointer;text-align:center;font-size: 24px;}\
        .datepicker__month{flex-grow:1;color:#000;font-size:1.3em;text-align:center}\
        .datepicker__year{display:flex;align-items:center;margin-left:6px;font-size:1.5em}\
        .datepicker__year-arrows{margin-left:5px}\
        .datepicker__week{display:flex;padding:0;margin:0 0 13px;list-style:none}\
        .datepicker__week li{width:14.28571%;color:#000;font-size:1em;line-height:1;text-align:center}\
        .datepicker__days{display:flex;flex-wrap:wrap;padding:0;margin:0;list-style:none}\
        .datepicker__days li{margin-bottom:2px;width:14.28571%}\
        .datepicker__days li span{display:flex;align-items:center;justify-content:center;width:30px;height:30px;border:1px solid transparent;border-radius:50%;color:#000;font-size:1rem;line-height:1;cursor:pointer;transition:all .3s}.datepicker__days li span:hover{border-color:#D0D0D0}.datepicker__days li span.is-active{background-color:#777;border-color:#777;color:#fff}\
        .datepicker__days li span.prev-month, .datepicker__days li span.next-month {color:#7d7d7d}\
        .datepicker__year-arrow{position: absolute;display:flex;width:10px;height:10px;cursor:pointer;font-weight: bold;}\
        .datepicker__year-arrow.up {top:16px; font-size:0.8em;}\
        .datepicker__year-arrow.down {top:24px; font-size:0.7em; margin-left: 1px;}\
        .datepicker .control {margin-right: 0px;}\
    ';

    return {
        /**
         * @param data
         * @return {void}
         */
        build: data => {
            let options = {
                "class" : "datepicker",
                "placeholder" : "ДД.ММ.ГГГГ",
                "daysOfWeek" : ["Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"],
                "monthNames" : ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"],
            };

            data = data || {};
            for (let ind in data) {
                if (data.hasOwnProperty(ind)) {
                    options[ind] = data[ind];
                }
            }

            dom.ready(function() {
                let datepickerInputs = dom.findAllByClass(options["class"]);
                obj.forEach(datepickerInputs, function(datepickerInput) {

                    const template = '\
<div class="form__field datepicker-wrapper" id="datepicker-wrapper-{{ id }}">\
    <div class="input-field input-field--append">\
        <input class="datepicker-input" id="datepicker-input-{{ id }}" value="{{ value }}" placeholder="{{ placeholder }}"/>\
        <div class="hidden datepicker" id="datepicker-{{ id }}">\
            <div class="datepicker__nav">\
                <div class="datepicker__nav-action on-prev-month" id="on-prev-month-{{ id }}"><</div>\
                <div class="datepicker__nav-content">\
                    <div class="datepicker__month">{{ curMonthName }}</div>\
                    <div class="datepicker__year">{{ curYear }}\
                        <div class="datepicker__year-arrows">\
                            <div class="datepicker__year-arrow up on-next-year" id="on-next-year-{{ id }}">^</div>\
                            <div class="datepicker__year-arrow down on-prev-year" id="on-prev-year-{{ id }}">v</div>\
                        </div>\
                    </div>\
                </div>\
                <div class="datepicker__nav-action control on-next-month" id="on-next-month-{{ id }}">></div>\
            </div>\
            <ul class="datepicker__week">{{ daysOfWeek }}</ul>\
            <ul class="datepicker__days">{{ datepickerDays }}</ul>\
        </div>\
    </div>\
</div>';

                    let
                        hidden = true,
                        id = (new Date()).getTime(),
                        daysOfWeek = "",
                        value,
                        curMonthName,
                        curYear,
                        placeholder,
                        datepickerDays,
                        curMonth,
                        selectedYear,
                        selectedMonth,
                        selectedDate,
                        curMonthDays,
                        prevMonthDays,
                        nextMonthDays,
                        hide
                    ;
                    /**
                     * Считает количество дней в месяце month года year
                     * @param month
                     * @param year
                     */
                    const getDaysInMonth = (month, year) => 32 - new Date(year, month, 32).getDate();

                    /**
                     * Заполняет массив рядом числел от start до end
                     * @param start
                     * @param end
                     */
                    const range = (start, end) => [...Array(end - start + 1)].map((_, key) => key + start);

                    /**
                     * Переводит день недели из американского в русский
                     * @param day
                     */
                    const ruDaysOfWeek = day => [6, 0, 1, 2, 3, 4, 5][day];

                    const refreshTemplate = datepickerWrapper => {
                        if (datepickerWrapper===undefined) {
                            datepickerWrapper = dom.findById("datepicker-wrapper-" + id);
                        }
                        dom.replace(datepickerWrapper, dom.renderTemplate(template, {
                            "id" : id,
                            "value" : value,
                            "curMonthName" : curMonthName,
                            "curYear" : curYear,
                            "placeholder" : placeholder,
                            "daysOfWeek" : daysOfWeek,
                            "datepickerDays" : datepickerDays,
                        }));

                        const datepickerInput = dom.findById("datepicker-input-" + id);
                        const datepicker = dom.findById("datepicker-" + id);

                        if (hidden === false) {
                            dom.removeClass(datepicker, "hidden");
                        }

                        // навешиваем обработчики события
                        // показать или спрятать окно при клике на инпут
                        dom.click(datepickerInput, (e) => {
                            showDatepicker(datepicker);
                            e.stopPropagation();
                        });
                        dom.click(datepicker, (e) => {
                            hide = false;
                            e.stopPropagation();
                        });
                        dom.click(window, (e) => {
                            if (hide) {
                                hideDatepicker(datepicker);
                            }
                            hide = true;
                        });

                        // навигация
                        dom.click(dom.findById("on-prev-month-" + id), () => {
                            curMonth--;
                            if (curMonth === -1) {
                                curMonth = 11;
                                curYear--;
                            }
                            buildDatepicker();
                        });
                        dom.click(dom.findById("on-next-month-" + id), () => {
                            curMonth++;
                            if (curMonth === 12) {
                                curMonth = 0;
                                curYear++;
                            }
                            buildDatepicker();
                        });
                        dom.click(dom.findById("on-prev-year-" + id), () => {
                            curYear--;
                            buildDatepicker();
                        });
                        dom.click(dom.findById("on-next-year-" + id), () => {
                            curYear++;
                            buildDatepicker();
                        });

                        // дни календаря
                        setDateHandlers(prevMonthDays, curMonth - 1);
                        setDateHandlers(curMonthDays, curMonth);
                        setDateHandlers(nextMonthDays, curMonth + 1);
                    };

                    const setDateHandlers = (days, month) => {
                        for (let i in days) {
                            let date = days[i];
                            // Заполняем input
                            dom.click(dom.findById("datepicker-date-" + id + date + month), () => {
                                hidden = true;

                                selectedDate = date;
                                selectedMonth = month;
                                if (curYear !== undefined) {
                                    selectedYear = curYear;
                                }
                                if (selectedMonth === -1) {
                                    selectedMonth = 11;
                                    selectedYear--;
                                }
                                if (selectedMonth === 12) {
                                    selectedMonth = 0;
                                    selectedYear++;
                                }
                                curMonth = selectedMonth;
                                formatInputDate();
                                buildDatepicker();
                            });
                        }
                    };

                    /**
                     * Высчитываем дни календаря
                     */
                    const buildDatepicker = (datepicker) => {
                        curMonthName = options.monthNames[curMonth];
                        // день недели первого дня месяца
                        let firstDay = (new Date(curYear, curMonth)).getDay();
                        firstDay = ruDaysOfWeek(firstDay);
                        // высчитываем дни текущего месяца
                        const daysInMonth = getDaysInMonth(curMonth, curYear);
                        curMonthDays = range(1, daysInMonth);
                        // высчитываем дни предыдущего месяца
                        const dateTime = new Date(curYear, curMonth);
                        dateTime.setDate(0);
                        const prevLastDate = dateTime.getDate();
                        const prevFirstDate = prevLastDate - firstDay + 1;
                        prevMonthDays = range(prevFirstDate, prevLastDate);
                        // высчитываем дни следующего месяца
                        dateTime.setDate(daysInMonth + 1);
                        const nextLastDate = 42 - daysInMonth - (prevLastDate - prevFirstDate) - 1;
                        nextMonthDays = range(1, nextLastDate);

                        datepickerDays = "";
                        setDatepickerDays(prevMonthDays, curMonth - 1, 'prev-month');
                        setDatepickerDays(curMonthDays, curMonth, 'curr-month');
                        setDatepickerDays(nextMonthDays, curMonth + 1, 'next-month');

                        refreshTemplate(datepicker);
                    };

                    const setDatepickerDays = (monthDays, month, spanClass) => {
                        for (let i in monthDays) {
                            let _spanClass = spanClass;
                            let date = monthDays[i];
                            // Является ли день календаря сегодняшним или выбранным
                            if (date === selectedDate && month === selectedMonth && curYear === selectedYear) {
                                _spanClass += ' is-active';
                            }
                            datepickerDays += '<li><span id="datepicker-date-' + id + date + month + '" class="' + _spanClass + '">' + date + '</span></li>';
                        }
                    };

                    const showDatepicker = function (datepicker) {
                        hidden = false;
                        dom.removeClass(datepicker, "hidden");
                    };

                    const hideDatepicker = datepicker => {
                        hidden = true;
                        dom.addClass(datepicker, "hidden");
                    };

                    const formatInputDate = () => {
                        const dateFormatted = selectedDate.toString().padStart(2, '0');
                        const monthFormatted = (selectedMonth + 1).toString().padStart(2, '0');

                        value = dateFormatted + '.' + monthFormatted + '.' + selectedYear;
                    };

                    placeholder = options.placeholder;
                    obj.forEach(options.daysOfWeek, function(day) {
                        daysOfWeek += "<li>" + day + "</li>"
                    });

                    let date = dom.val(datepickerInput);
                    if (date !== '' && date !== null && date !== undefined) {
                        [selectedYear, selectedMonth, selectedDate] = date.split('-').map((val) => parseInt(val));
                        selectedMonth--;
                    } else {
                        const curDateTime = new Date();
                        selectedDate = curDateTime.getDate();
                        selectedMonth = curDateTime.getMonth();
                        selectedYear = curDateTime.getFullYear();
                    }
                    curMonth = selectedMonth;
                    curYear = selectedYear;
                    formatInputDate();
                    buildDatepicker(datepickerInput);
                });
                // применяем стили
                var style = document.createElement("style");
                style.innerHTML = styles;
                document.getElementsByTagName("head")[0].appendChild(style);
            });
        },
    };
})();

