import { Grid, TextField, MenuItem, Select, FormControl, InputLabel, Stack, Typography } from '@mui/material';

const ROLES = ['SuperAdmin', 'Developer', 'Client'];
const COUNTRIES = ['United Kingdom', 'United States', 'Canada', 'Australia', 'Germany', 'France', 'India', 'Other'];

/**
 * ProfileForm — controlled form for all profile fields.
 * Props: values, onChange(field, value), errors
 */
export default function ProfileForm({ values = {}, onChange, errors = {} }) {
  const set = (field) => (e) => onChange(field, e.target.value);

  const Field = ({ name, label, type = 'text', required, ...rest }) => (
    <TextField
      label={label}
      type={type}
      fullWidth
      size="small"
      value={values[name] ?? ''}
      onChange={set(name)}
      error={!!errors[name]}
      helperText={errors[name]}
      required={required}
      {...rest}
    />
  );

  return (
    <Grid container spacing={2.5}>
      <Grid item xs={12}>
        <Typography variant="subtitle2" color="text.secondary" sx={{ mb: 0.5, fontWeight: 600, textTransform: 'uppercase', fontSize: '0.68rem', letterSpacing: '0.08em' }}>
          Personal Info
        </Typography>
      </Grid>

      <Grid item xs={12} sm={6}><Field name="fullName"    label="Full Name"     required /></Grid>
      <Grid item xs={12} sm={6}><Field name="email"       label="Email Address" type="email" required /></Grid>
      <Grid item xs={12} sm={6}><Field name="phone"       label="Phone Number" /></Grid>
      <Grid item xs={12} sm={6}><Field name="company"     label="Company Name" /></Grid>

      <Grid item xs={12}>
        <FormControl fullWidth size="small" error={!!errors.role}>
          <InputLabel>Role</InputLabel>
          <Select value={values.role ?? ''} label="Role" onChange={(e) => onChange('role', e.target.value)}>
            {ROLES.map((r) => <MenuItem key={r} value={r}>{r}</MenuItem>)}
          </Select>
        </FormControl>
      </Grid>

      <Grid item xs={12}>
        <Typography variant="subtitle2" color="text.secondary" sx={{ mt: 0.5, mb: 0.5, fontWeight: 600, textTransform: 'uppercase', fontSize: '0.68rem', letterSpacing: '0.08em' }}>
          Address
        </Typography>
      </Grid>

      <Grid item xs={12}><Field name="address" label="Street Address" /></Grid>
      <Grid item xs={12} sm={6}><Field name="city"    label="City" /></Grid>
      <Grid item xs={12} sm={6}>
        <FormControl fullWidth size="small">
          <InputLabel>Country</InputLabel>
          <Select value={values.country ?? ''} label="Country" onChange={(e) => onChange('country', e.target.value)}>
            {COUNTRIES.map((c) => <MenuItem key={c} value={c}>{c}</MenuItem>)}
          </Select>
        </FormControl>
      </Grid>
      <Grid item xs={12} sm={6}><Field name="zip" label="Zip / Postal Code" /></Grid>

      <Grid item xs={12}>
        <Typography variant="subtitle2" color="text.secondary" sx={{ mt: 0.5, mb: 0.5, fontWeight: 600, textTransform: 'uppercase', fontSize: '0.68rem', letterSpacing: '0.08em' }}>
          About
        </Typography>
      </Grid>
      <Grid item xs={12}>
        <Field name="bio" label="Bio" multiline rows={3} />
      </Grid>
    </Grid>
  );
}
