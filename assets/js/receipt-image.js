/* Wear Adinkra — canvas receipt image generator.
   Draws the receipt from localStorage and downloads it as a PNG. */
(function () {
    'use strict';

    var NAVY = '#0B132B';
    var SEA = '#1C5D99';
    var LIGHT = '#8CB9E8';
    var MUTED = '#6B7280';
    var LINE = '#E5E7EB';
    var FONT = "'Century Gothic', 'Avant Garde', 'ITC Avant Garde Gothic', sans-serif";
    var KENTE = ['#E3251B', '#F4C20D', '#0E8A4F', '#0B132B'];

    function money(n) {
        return 'GH₵ ' + Number(n || 0).toFixed(2);
    }

    function findOrder(orderId) {
        var orders = [];
        try { orders = JSON.parse(localStorage.getItem('wa_orders') || '[]') || []; } catch (e) { orders = []; }
        if (!Array.isArray(orders)) orders = [];
        if (orderId) {
            var match = orders.find(function (o) { return String(o && o.id) === String(orderId); });
            if (match) return match;
        }
        try {
            var last = JSON.parse(localStorage.getItem('wa_last_order') || 'null');
            if (last) {
                var lastMatch = orders.find(function (o) { return String(o && o.id) === String(last); });
                if (lastMatch) return lastMatch;
            }
        } catch (e) { /* ignore */ }
        return orders[0] || null;
    }

    window.downloadReceiptImage = function (orderId) {
        var order = findOrder(orderId);
        if (!order) { alert('Receipt not found.'); return; }

        var items = Array.isArray(order.items) ? order.items : [];
        var customer = order.customer || {};
        var W = 760;
        var PAD = 44;
        var CONTENT = W - PAD * 2;
        var HEADER_H = 160;
        var BILL_Y = HEADER_H + 46;
        var BILL_H = 92;
        var TABLE_Y = BILL_Y + BILL_H + 44;
        var ROW_START = TABLE_Y + 26;
        var ROW_H = 36;
        var ROWS_BOTTOM = ROW_START + Math.max(1, items.length) * ROW_H;
        var TOTALS_Y = ROWS_BOTTOM + 46;
        var FOOTER_Y = TOTALS_Y + 96;
        var H = FOOTER_Y + 74;
        var scale = 2;

        var canvas = document.createElement('canvas');
        canvas.width = W * scale;
        canvas.height = H * scale;
        var ctx = canvas.getContext('2d');
        ctx.scale(scale, scale);

        function font(weight, size) { return weight + ' ' + size + 'px ' + FONT; }
        function text(value, x, y, opts) {
            opts = opts || {};
            ctx.font = font(opts.weight || '400', opts.size || 13);
            ctx.fillStyle = opts.color || NAVY;
            ctx.textAlign = opts.align || 'left';
            ctx.textBaseline = opts.baseline || 'alphabetic';
            ctx.fillText(String(value == null ? '' : value), x, y);
        }
        function fit(value, maxWidth, weight, size) {
            var f = font(weight || '400', size || 13);
            var str = String(value == null ? '' : value);
            ctx.font = f;
            if (ctx.measureText(str).width <= maxWidth) return str;
            var out = str;
            while (out.length > 1 && ctx.measureText(out + '…').width > maxWidth) out = out.slice(0, -1);
            return out + '…';
        }
        function line(x1, y1, x2, y2, color, width) {
            ctx.beginPath();
            ctx.strokeStyle = color || LINE;
            ctx.lineWidth = width || 1;
            ctx.moveTo(x1, y1);
            ctx.lineTo(x2, y2);
            ctx.stroke();
        }

        function build(logoImg) {
            /* Background + border */
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, W, H);
            ctx.strokeStyle = LINE;
            ctx.lineWidth = 1;
            ctx.strokeRect(0.5, 0.5, W - 1, H - 1);

            /* Header band */
            ctx.fillStyle = NAVY;
            ctx.fillRect(0, 0, W, HEADER_H);

            if (logoImg && logoImg.complete && logoImg.naturalWidth) {
                try { ctx.drawImage(logoImg, PAD, 34, 70, 70); } catch (e) { /* skip logo */ }
                text('Wear Adinkra', PAD + 84, 74, { color: '#FFFFFF', weight: '700', size: 28 });
            } else {
                text('Wear Adinkra', PAD, 78, { color: '#FFFFFF', weight: '700', size: 30 });
            }
            text('Our heritage lives on…!', logoImg ? PAD + 86 : PAD, logoImg ? 100 : 104,
                { color: LIGHT, size: 12 });

            text('RECEIPT', W - PAD, 56, { align: 'right', color: LIGHT, size: 11, weight: '700' });
            text(fit(order.id || '—', 240, '700', 16), W - PAD, 84, { align: 'right', color: '#FFFFFF', weight: '700', size: 16 });
            text(fit(order.date || '', 260, '400', 12), W - PAD, 108, { align: 'right', color: '#CBD5E1', size: 12 });

            /* Kente accent strip */
            var seg = CONTENT / KENTE.length;
            KENTE.forEach(function (color, i) {
                ctx.fillStyle = color;
                ctx.fillRect(PAD + i * seg, HEADER_H - 5, seg + 0.5, 5);
            });

            /* Billed to / payment */
            text('BILLED TO', PAD, BILL_Y, { color: MUTED, size: 10, weight: '700' });
            text(fit(customer.name || 'Valued Customer', CONTENT / 2 - 20, '700', 15), PAD, BILL_Y + 24, { weight: '700', size: 15 });
            if (customer.address) text(fit(customer.address, CONTENT / 2 - 20, '400', 12), PAD, BILL_Y + 44, { color: MUTED, size: 12 });
            var contact = [customer.email, customer.phone].filter(Boolean).join(' · ');
            if (contact) text(fit(contact, CONTENT / 2 - 20, '400', 12), PAD, BILL_Y + 64, { color: MUTED, size: 12 });

            text('PAYMENT', W - PAD, BILL_Y, { align: 'right', color: MUTED, size: 10, weight: '700' });
            text(fit(String(order.method || '').toUpperCase(), CONTENT / 2 - 20, '700', 15), W - PAD, BILL_Y + 24, { align: 'right', weight: '700', size: 15 });
            text('Paid in full', W - PAD, BILL_Y + 44, { align: 'right', color: '#15803D', size: 12 });
            line(PAD, BILL_Y + BILL_H, W - PAD, BILL_Y + BILL_H, LINE, 1);

            /* Items table */
            ctx.fillStyle = '#F3F4F6';
            ctx.fillRect(PAD, TABLE_Y - 22, CONTENT, 30);
            text('ITEM', PAD + 10, TABLE_Y, { size: 10, weight: '700', color: MUTED });
            text('QTY', PAD + CONTENT * 0.62, TABLE_Y, { size: 10, weight: '700', color: MUTED, align: 'center' });
            text('AMOUNT', W - PAD - 10, TABLE_Y, { size: 10, weight: '700', color: MUTED, align: 'right' });

            if (!items.length) {
                text('No items recorded.', PAD + 10, ROW_START + 22, { color: MUTED, size: 13 });
            }
            items.forEach(function (item, i) {
                var y = ROW_START + i * ROW_H;
                var details = [item.size ? 'Size: ' + item.size : '', item.color ? 'Color: ' + item.color : '',
                    item.symbol ? 'Symbol: ' + item.symbol : '',
                    (item.kidAge || item.kidHeight || item.kidSex)
                        ? 'Child: ' + [item.kidAge || '', item.kidHeight || '', item.kidSex || ''].filter(Boolean).join(' · ')
                        : ''].filter(Boolean).join('  ·  ');
                text(fit(item.name || 'Item', CONTENT * 0.55, '700', 13), PAD + 10, y + 16, { weight: '700', size: 13 });
                if (details) text(fit(details, CONTENT * 0.55, '400', 10), PAD + 10, y + 30, { color: MUTED, size: 10 });
                text(String(item.quantity || 1), PAD + CONTENT * 0.62, y + 18, { size: 13, align: 'center' });
                text(money((item.price || 0) * (item.quantity || 1)), W - PAD - 10, y + 18, { size: 13, align: 'right' });
                line(PAD, y + ROW_H - 4, W - PAD, y + ROW_H - 4, '#F3F4F6', 1);
            });

            /* Totals */
            var tx = W - PAD - 10;
            text('Subtotal', tx - 140, TOTALS_Y, { align: 'right', color: MUTED, size: 13 });
            text(money(order.total), tx, TOTALS_Y, { align: 'right', size: 13 });
            line(tx - 200, TOTALS_Y + 16, tx, TOTALS_Y + 16, NAVY, 1.5);
            text('TOTAL', tx - 140, TOTALS_Y + 46, { align: 'right', weight: '700', size: 17 });
            text(money(order.total), tx, TOTALS_Y + 46, { align: 'right', weight: '700', size: 18, color: SEA });

            /* Footer */
            line(PAD, FOOTER_Y - 22, W - PAD, FOOTER_Y - 22, LINE, 1);
            text('Thank you for wearing Adinkra.', W / 2, FOOTER_Y, { align: 'center', size: 12, color: NAVY, weight: '700' });
            text('Made with love in Accra, Ghana.', W / 2, FOOTER_Y + 20, { align: 'center', size: 11, color: MUTED });
            text('Tracking number: ' + (order.id || '—'), W / 2, FOOTER_Y + 42, { align: 'center', size: 11, color: MUTED });

            /* Download */
            var safeId = String(order.id || 'receipt').replace(/[^A-Za-z0-9-]/g, '');
            var filename = 'Wear-Adinkra-Receipt-' + safeId + '.png';
            try {
                canvas.toBlob(function (blob) {
                    if (!blob) return;
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    setTimeout(function () { URL.revokeObjectURL(url); }, 1500);
                }, 'image/png');
            } catch (e) {
                var a2 = document.createElement('a');
                a2.href = canvas.toDataURL('image/png');
                a2.download = filename;
                document.body.appendChild(a2);
                a2.click();
                document.body.removeChild(a2);
            }
        }

        var built = false;
        function finish(img) {
            if (built) return;
            built = true;
            build(img);
        }
        var logo = new Image();
        logo.onload = function () { finish(logo); };
        logo.onerror = function () { finish(null); };
        logo.src = 'assets/wearadinkra-logo.png';
        setTimeout(function () { finish(null); }, 1500);
    };
})();
