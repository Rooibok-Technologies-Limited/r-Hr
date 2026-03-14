/**
 * Invoice Print & Thermal Receipt — Phase 7.1 + 7.2
 */

// Browser A4 print
function printInvoice() {
    window.print();
}

// Thermal receipt print via localhost Node.js service
async function printThermalReceipt(invoiceData) {
    try {
        const response = await fetch('http://localhost:6500/print-receipt', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(invoiceData)
        });
        if (response.ok) {
            toastr.success('Receipt printed successfully');
        } else {
            throw new Error('Print service error');
        }
    } catch (e) {
        // Fallback: open PDF in browser tab for manual print
        if (invoiceData.invoice_id) {
            window.open(site_url + '/erp/subscription-invoice-download/' + invoiceData.invoice_id, '_blank');
        } else {
            toastr.info('Print service not available. Opening PDF instead.');
            window.print();
        }
    }
}
