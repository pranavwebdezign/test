import { FormControl, InputLabel, MenuItem, Select } from '@mui/material';

/**
 * Shared select field helper used by EditSite and AddSite.
 */
export default function SelectField({ label, value, onChange, options, ...rest }) {
    return (
        <FormControl fullWidth size="small" {...rest}>
            <InputLabel>{label}</InputLabel>
            <Select value={value} onChange={(e) => onChange(e.target.value)} label={label}>
                {options.map((o) => (
                    <MenuItem key={o.value} value={o.value}>{o.label}</MenuItem>
                ))}
            </Select>
        </FormControl>
    );
}
