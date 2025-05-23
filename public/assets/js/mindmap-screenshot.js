/**
 * @license BSD-3-Clause
 * @copyright 2014-2023 hizzgdev@163.com
 *
 * Project Home:
 *   https://github.com/hizzgdev/jsmind/
 */
!(function (e, t) {
    "object" == typeof exports && "undefined" != typeof module
        ? t(require("jsmind"), require("dom-to-image"))
        : "function" == typeof define && define.amd
            ? define(["jsmind", "dom-to-image"], t)
            : t(
                (e = "undefined" != typeof globalThis ? globalThis : e || self)
                    .jsMind,
                e.domtoimage
            );
})(this, function (e, t) {
    "use strict";
    function i(e) {
        return e && "object" == typeof e && "default" in e ? e : { default: e };
    }
    var n = i(e),
        o = i(t);
    if (!n.default) throw new Error("jsMind is not defined");
    if (!o.default) throw new Error("dom-to-image is required");
    const r = n.default.$,
        s = {
            filename: null,
            watermark: {
                left: r.w.location,
                right: "https://github.com/hizzgdev/jsmind",
            },
            background: "#fff",
        };
    class a {
        constructor(e, t) {
            var i = {};
            n.default.util.json.merge(i, s),
                n.default.util.json.merge(i, t),
                (this.version = "0.2.0"),
                (this.jm = e),
                (this.options = i),
                (this.dpr = e.view.device_pixel_ratio);
        }
        shoot() {
            let e = this.create_canvas(),
                t = e.getContext("2d");
            t.scale(this.dpr, this.dpr),
                Promise.resolve(t)
                    .then(() => this.draw_background(t))
                    .then(() => this.draw_lines(t))
                    .then(() => this.draw_nodes(t))
                    .then(() => this.draw_watermark(e, t))
                    .then(() => this.download(e))
                    .then(() => this.clear(e));
        }
        create_canvas() {
            let e = r.c("canvas");
            const t = this.jm.view.size.w,
                i = this.jm.view.size.h;
            return (
                (e.width = t * this.dpr),
                (e.height = i * this.dpr),
                (e.style.width = t + "px"),
                (e.style.height = i + "px"),
                (e.style.visibility = "hidden"),
                this.jm.view.e_panel.appendChild(e),
                e
            );
        }
        clear(e) {
            e.parentNode.removeChild(e);
        }
        draw_background(e) {
            return new Promise(
                function (t, i) {
                    const n = this.options.background;
                    n &&
                        "transparent" !== n &&
                        ((e.fillStyle = this.options.background),
                            e.fillRect(
                                0,
                                0,
                                this.jm.view.size.w,
                                this.jm.view.size.h
                            )),
                        t(e);
                }.bind(this)
            );
        }
        draw_lines(e) {
            return new Promise(
                function (t, i) {
                    this.jm.view.graph.copy_to(e, function () {
                        t(e);
                    });
                }.bind(this)
            );
        }
        draw_nodes(e) {
            return o.default
                .toSvg(this.jm.view.e_nodes, { style: { zoom: 1 } })
                .then(this.load_image)
                .then(function (t) {
                    return e.drawImage(t, 0, 0), e;
                });
        }
        draw_watermark(e, t) {
            return (
                (t.textBaseline = "bottom"),
                (t.fillStyle = "#000"),
                (t.font = "11px Verdana,Arial,Helvetica,sans-serif"),
                this.options.watermark.left &&
                ((t.textAlign = "left"),
                    t.fillText(
                        this.options.watermark.left,
                        5.5,
                        e.height - 2.5
                    )),
                this.options.watermark.right &&
                ((t.textAlign = "right"),
                    t.fillText(
                        this.options.watermark.right,
                        e.width - 5.5,
                        e.height - 2.5
                    )),
                t
            );
        }
        load_image(e) {
            return new Promise(function (t, i) {
                let n = new Image();
                (n.onload = function () {
                    t(n);
                }),
                    (n.onerror = i),
                    (n.src = e);
            });
        }
        download(e) {
            var t = (this.options.filename || this.jm.mind.name) + ".png";
            if (navigator.msSaveBlob && e.msToBlob) {
                var i = e.msToBlob();
                navigator.msSaveBlob(i, t);
            } else {
                var n = e.toDataURL(),
                    o = r.c("a");
                if ("download" in o) {
                    (o.style.visibility = "hidden"),
                        (o.href = n),
                        (o.download = t),
                        r.d.body.appendChild(o);
                    var s = r.d.createEvent("MouseEvents");
                    s.initEvent("click", !0, !0),
                        o.dispatchEvent(s),
                        r.d.body.removeChild(o);
                } else location.href = n;
            }
        }
    }
    let d = new n.default.plugin("screenshot", function (e, t) {
        var i = new a(e, t);
        (e.screenshot = i),
            (e.shoot = function () {
                i.shoot();
            });
    });
    n.default.register_plugin(d);
});
//# sourceMappingURL=jsmind.screenshot.js.map
