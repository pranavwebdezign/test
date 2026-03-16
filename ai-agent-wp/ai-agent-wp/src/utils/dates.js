/**
 * Format an ISO date string or null/undefined as DD/MM/YYYY.
 * Returns '—' when the value is empty.
 */
export function fmtDate(value) {
    if (!value) return '—';
    try {
        const d = new Date(value);
        if (isNaN(d.getTime())) return value; // return raw if unparseable
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yyyy = d.getFullYear();
        return `${dd}/${mm}/${yyyy}`;
    } catch {
        return value;
    }
}

/**
 * Format an ISO datetime string as DD/MM/YYYY HH:MM (24h).
 * Returns '—' when the value is empty.
 */
export function fmtDatetime(value) {
    if (!value) return '—';
    try {
        const d = new Date(value);
        if (isNaN(d.getTime())) return value;
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yyyy = d.getFullYear();
        const hh = String(d.getHours()).padStart(2, '0');
        const mi = String(d.getMinutes()).padStart(2, '0');
        return `${dd}/${mm}/${yyyy} ${hh}:${mi}`;
    } catch {
        return value;
    }
}

/**
 * Convert a DD/MM/YYYY string to YYYY-MM-DD for HTML date inputs.
 */
export function toInputDate(value) {
    if (!value) return '';
    // Already in YYYY-MM-DD format?
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
    const [dd, mm, yyyy] = value.split('/');
    return `${yyyy}-${mm}-${dd}`;
}
