import jsPDF from 'jspdf'

export interface ReceiptData {
  booking_reference?: string
  tx_ref?: string
  first_name?: string
  last_name?: string
  email?: string
  phone?: string
  check_in_date?: string
  check_out_date?: string
  room_number?: string
  number_of_guests?: number
  total_amount?: number | string
  payment_date?: string
  currency?: string
  status?: string
  special_requests?: string
}

/**
 * Generate and download receipt as PDF (Direct PDF generation - no html2canvas)
 */
export async function generateAndDownloadReceipt(data: ReceiptData): Promise<void> {
  try {
    console.log('📄 [RECEIPT] Generating receipt PDF...')
    console.log('📦 [RECEIPT] Receipt data:', data)

    // Create PDF
    console.log('📝 [RECEIPT] Creating PDF document...')
    const pdf = new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4',
    })

    // Setup
    const pageWidth = pdf.internal.pageSize.getWidth()
    const margin = 15
    const contentWidth = pageWidth - 2 * margin
    let yPosition = margin

    // Helper function to add text
    const addText = (text: string, size: number, bold: boolean = false, color: [number, number, number] = [0, 0, 0]) => {
      pdf.setFontSize(size)
      pdf.setFont('helvetica', bold ? 'bold' : 'normal')
      pdf.setTextColor(color[0], color[1], color[2])
      const lines = pdf.splitTextToSize(text, contentWidth)
      pdf.text(lines, margin, yPosition)
      yPosition += size * 0.7 * lines.length + 3
    }

    const addLine = () => {
      pdf.setDrawColor(16, 185, 129) // Green color
      pdf.line(margin, yPosition, pageWidth - margin, yPosition)
      yPosition += 5
    }

    const addSection = (title: string) => {
      pdf.setFontSize(11)
      pdf.setFont('helvetica', 'bold')
      pdf.setTextColor(16, 185, 129)
      pdf.text(title, margin, yPosition)
      yPosition += 7
    }

    // --- HEADER ---
    pdf.setFontSize(24)
    pdf.setFont('helvetica', 'bold')
    pdf.setTextColor(16, 185, 129)
    pdf.text('RECEIPT', pageWidth / 2, yPosition, { align: 'center' })
    yPosition += 10

    pdf.setFontSize(10)
    pdf.setFont('helvetica', 'normal')
    pdf.setTextColor(100, 100, 100)
    pdf.text('Hotel Reservation Payment', pageWidth / 2, yPosition, { align: 'center' })
    yPosition += 8

    addLine()
    yPosition += 3

    // --- HOTEL INFO ---
    addSection('HOTEL INFORMATION')
    addText('Royal Horizon Hotel', 11, true)
    addText('Addis Ababa, Ethiopia', 9)
    addText('📞 +251 911 234 567', 9)
    addText('✉️ info@royalhorizon.com', 9)
    yPosition += 5

    // --- GUEST INFO ---
    addSection('GUEST INFORMATION')
    addText(`Name: ${data.first_name || 'Guest'} ${data.last_name || ''}`, 9)
    addText(`Email: ${data.email || 'N/A'}`, 9)
    addText(`Phone: ${data.phone || 'N/A'}`, 9)
    addText(`Guests: ${data.number_of_guests || 1}`, 9)
    yPosition += 5

    // --- BOOKING DETAILS ---
    addSection('BOOKING DETAILS')
    addText(`Reference: ${data.booking_reference || 'REF-' + (data.tx_ref?.substring(0, 8) || 'N/A').toUpperCase()}`, 9)
    
    const checkInDate = data.check_in_date
      ? new Date(data.check_in_date).toLocaleDateString('en-ET', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
        })
      : 'N/A'
    
    const checkOutDate = data.check_out_date
      ? new Date(data.check_out_date).toLocaleDateString('en-ET', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
        })
      : 'N/A'

    addText(`Check-in: ${checkInDate}`, 9)
    addText(`Check-out: ${checkOutDate}`, 9)
    addText(`Room: ${data.room_number || 'Pending'}`, 9)
    
    if (data.special_requests) {
      addText(`Special Requests: ${data.special_requests}`, 9)
    }
    yPosition += 5

    // --- PAYMENT SUMMARY ---
    addSection('PAYMENT SUMMARY')
    addText(`Transaction Reference: ${data.tx_ref || 'N/A'}`, 9)
    yPosition += 3
    
    pdf.setFontSize(16)
    pdf.setFont('helvetica', 'bold')
    pdf.setTextColor(217, 119, 6) // Orange
    const amountText = `${data.total_amount || '0'} ${data.currency || 'ETB'}`
    pdf.text(`TOTAL AMOUNT PAID: ${amountText}`, pageWidth / 2, yPosition, { align: 'center' })
    yPosition += 12

    // --- TERMS ---
    addSection('TERMS & CONDITIONS')
    pdf.setFontSize(8)
    pdf.setFont('helvetica', 'normal')
    pdf.setTextColor(0, 0, 0)
    
    const terms = [
      '• Cancellation must be done 48 hours before check-in for refund',
      '• No-show charges will apply 24 hours before check-in',
      '• Payment has been securely processed through Chapa Payment Gateway',
      '• Keep this receipt for your records',
      '• For inquiries or modifications, contact us immediately',
    ]
    
    terms.forEach(term => {
      const lines = pdf.splitTextToSize(term, contentWidth)
      pdf.text(lines, margin + 2, yPosition)
      yPosition += 4
    })

    yPosition += 5

    // --- FOOTER ---
    pdf.setFontSize(10)
    pdf.setFont('helvetica', 'bold')
    pdf.setTextColor(16, 185, 129)
    pdf.text('✓ PAYMENT CONFIRMED', pageWidth / 2, yPosition, { align: 'center' })
    yPosition += 6

    pdf.setFontSize(9)
    pdf.setFont('helvetica', 'normal')
    pdf.setTextColor(100, 100, 100)
    pdf.text('Thank you for your business!', pageWidth / 2, yPosition, { align: 'center' })
    yPosition += 5

    pdf.setFontSize(8)
    pdf.setTextColor(150, 150, 150)
    const now = new Date().toLocaleDateString('en-ET', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })
    pdf.text(`Generated on ${now}`, pageWidth / 2, yPosition, { align: 'center' })

    // Save the PDF
    const fileName = `Receipt_${data.booking_reference || data.tx_ref?.substring(0, 8) || 'Payment'}_${new Date().toISOString().split('T')[0]}.pdf`
    console.log(`💾 [RECEIPT] Downloading receipt as ${fileName}...`)
    pdf.save(fileName)

    console.log('✅ [RECEIPT] Receipt downloaded successfully!')
  } catch (error: any) {
    console.error('❌ [RECEIPT] Error generating receipt:', error)
    console.error('❌ [RECEIPT] Error details:', {
      message: error.message,
      stack: error.stack,
    })
    throw new Error(`Failed to generate receipt: ${error.message}`)
  }
}

export default {
  generateAndDownloadReceipt,
}
