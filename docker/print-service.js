/**
 * Rooibok HR System — Thermal Receipt Print Service
 *
 * Runs on the reception computer (NOT on the server).
 * Install: npm install node-thermal-printer express
 * Run:     node print-service.js
 *
 * The web app sends print jobs to http://localhost:6500/print-receipt
 * If this service is not running, the app falls back to PDF browser print.
 */

const express = require('express');
const { ThermalPrinter, PrinterTypes } = require('node-thermal-printer');
const app = express();
app.use(express.json());

// CORS — allow requests from any origin (local browser)
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Headers', 'Content-Type');
    next();
});

app.post('/print-receipt', async (req, res) => {
    try {
        const { company, plan, amount, date, invoice_no, expiry } = req.body;
        const printer = new ThermalPrinter({
            type: PrinterTypes.EPSON,
            interface: 'usb' // or 'tcp://192.168.1.100:9100' for network printer
        });

        printer.alignCenter();
        printer.bold(true);
        printer.println('ROOIBOK HR SYSTEM');
        printer.bold(false);
        printer.println('Subscription Receipt');
        printer.drawLine();
        printer.alignLeft();
        printer.println(`Invoice: ${invoice_no}`);
        printer.println(`Company: ${company}`);
        printer.println(`Plan:    ${plan}`);
        printer.println(`Amount:  UGX ${Number(amount).toLocaleString()}`);
        printer.println(`Date:    ${date}`);
        printer.println(`Expires: ${expiry}`);
        printer.drawLine();
        printer.alignCenter();
        printer.println('Thank you!');
        printer.println('rooibok.co.ug');
        printer.cut();

        await printer.execute();
        res.json({ success: true });
    } catch (err) {
        console.error('Print error:', err.message);
        res.status(500).json({ success: false, error: err.message });
    }
});

app.get('/health', (req, res) => {
    res.json({ status: 'ok', service: 'rooibok-print-service' });
});

app.listen(6500, () => console.log('Rooibok Print Service running on port 6500'));
