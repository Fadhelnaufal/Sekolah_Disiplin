/**
 * Utility functions for WhatsApp notification integration.
 */

/**
 * Normalizes an Indonesian phone number to international format (628...).
 * Strips non-digits, leading zero, or leading '+'.
 */
export function normalizeWhatsAppNumber(phone?: string | null): string {
    if (!phone) return '';
    let cleaned = phone.replace(/\D/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.slice(1);
    } else if (cleaned.startsWith('8')) {
        cleaned = '62' + cleaned;
    }
    return cleaned;
}

/**
 * Formats a WhatsApp Web / App intent URL.
 */
export function buildWhatsAppUrl(phone: string | null | undefined, message: string): string {
    const normalizedPhone = normalizeWhatsAppNumber(phone);
    const encodedText = encodeURIComponent(message);
    if (normalizedPhone) {
        return `https://wa.me/${normalizedPhone}?text=${encodedText}`;
    }
    return `https://wa.me/?text=${encodedText}`;
}

/**
 * Creates a formal and courteous WhatsApp message for parent call / official letters.
 */
export function buildParentCallWhatsAppMessage(params: {
    schoolName: string;
    studentName: string;
    studentNis?: string;
    className?: string;
    letterNumber?: string;
    letterSubject?: string;
    meetingDayDate?: string;
    meetingTime?: string;
    meetingVenue?: string;
    meetingPurpose?: string;
    notes?: string;
}): string {
    const lines: string[] = [
        `Assalamu'alaikum Wr. Wb. / Salam Sejahtera`,
        ``,
        `Yth. Bapak/Ibu Wali Murid dari ananda:`,
        `*Nama Siswa:* ${params.studentName}`,
        params.className ? `*Kelas:* ${params.className}` : '',
        params.studentNis ? `*NIS:* ${params.studentNis}` : '',
        ``,
        `Kami dari pihak *${params.schoolName}* menyampaikan surat resmi mengenai:`,
        `*Perihal:* ${params.letterSubject || 'Pemanggilan Orang Tua / Wali Murid'}`,
        params.letterNumber ? `*Nomor Surat:* ${params.letterNumber}` : '',
        ``,
        `Sehubungan dengan hal tersebut, kami mengharap kehadiran Bapak/Ibu pada:`,
        `*Hari, Tanggal:* ${params.meetingDayDate || 'Sesuai jadwal terlampir'}`,
        `*Waktu:* ${params.meetingTime || '08.30 WIB s/d selesai'}`,
        `*Tempat:* ${params.meetingVenue || 'Ruang Bimbingan dan Konseling (BK)'}`,
        params.meetingPurpose ? `*Keperluan:* ${params.meetingPurpose}` : '',
        params.notes ? `*Catatan:* ${params.notes}` : '',
        ``,
        `Kehadiran Bapak/Ibu sangat penting demi pendampingan dan pembinaan terbaik bagi masa depan ananda.`,
        ``,
        `Terima kasih atas perhatian dan kerja samanya.`,
        `Hormat kami,`,
        `*${params.schoolName}*`,
    ];

    return lines.filter(line => line !== '').join('\n');
}

/**
 * Creates a formal WhatsApp notification message for student violations or achievements.
 */
export function buildDisciplineEventWhatsAppMessage(params: {
    schoolName: string;
    studentName: string;
    studentNis?: string;
    className?: string;
    eventType: 'violation' | 'achievement' | string;
    eventName: string;
    eventDate: string;
    points: number;
    description?: string;
    recorderName?: string;
}): string {
    const isViolation = params.eventType === 'violation';
    const statusText = isViolation ? 'Pelanggaran Tata Tertib' : 'Capaian Prestasi';
    const pointText = isViolation ? `+${params.points} Poin` : `-${Math.abs(params.points)} Poin (Apresiasi)`;

    const lines: string[] = [
        `Assalamu'alaikum Wr. Wb. / Salam Sejahtera`,
        ``,
        `Yth. Bapak/Ibu Wali Murid dari ananda:`,
        `*Nama Siswa:* ${params.studentName}`,
        params.className ? `*Kelas:* ${params.className}` : '',
        params.studentNis ? `*NIS:* ${params.studentNis}` : '',
        ``,
        `Kami dari *${params.schoolName}* menginformasikan catatan kedisiplinan ananda per tanggal *${params.eventDate}*:`,
        `*Kategori:* ${statusText}`,
        `*Kejadian:* ${params.eventName}`,
        `*Bobot:* ${pointText}`,
        params.description ? `*Keterangan:* ${params.description}` : '',
        params.recorderName ? `*Dicatat oleh:* ${params.recorderName}` : '',
        ``,
        isViolation
            ? `Pemberitahuan ini kami sampaikan sebagai bentuk transparansi dan sinergi bersama antara pihak sekolah dan orang tua agar ananda senantiasa terbimbing dengan baik.`
            : `Selamat atas capaian ananda, semoga menjadi motivasi bagi perkembangan prestasi selanjutnya.`,
        ``,
        `Terima kasih atas perhatian dan dukungannya.`,
        `Hormat kami,`,
        `*${params.schoolName}*`,
    ];

    return lines.filter(line => line !== '').join('\n');
}

