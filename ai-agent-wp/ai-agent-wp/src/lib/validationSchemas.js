import { z } from 'zod';

// ── Auth ──────────────────────────────────────────────────────────────────────

export const loginSchema = z.object({
  email:    z.string().min(1, 'Email is required').email('Invalid email address'),
  password: z.string().min(4, 'Password must be at least 4 characters'),
});

export const signupSchema = z.object({
  name:            z.string().min(2, 'Name must be at least 2 characters').max(100, 'Name too long'),
  email:           z.string().min(1, 'Email is required').email('Invalid email address'),
  company:         z.string().max(150, 'Company name too long').optional().or(z.literal('')),
  password:        z.string().min(8, 'Password must be at least 8 characters'),
  confirmPassword: z.string().min(1, 'Please confirm your password'),
}).refine((d) => d.password === d.confirmPassword, {
  message: 'Passwords do not match',
  path: ['confirmPassword'],
});

export const forgotPasswordSchema = z.object({
  email: z.string().min(1, 'Email is required').email('Invalid email address'),
});

export const resetPasswordSchema = z.object({
  password:        z.string().min(8, 'Password must be at least 8 characters'),
  confirmPassword: z.string().min(1, 'Please confirm your password'),
}).refine((d) => d.password === d.confirmPassword, {
  message: 'Passwords do not match',
  path: ['confirmPassword'],
});

// ── Profile ───────────────────────────────────────────────────────────────────

export const profileSchema = z.object({
  fullName: z.string().min(2, 'Full name must be at least 2 characters').max(100, 'Name too long'),
  email:    z.string().min(1, 'Email is required').email('Invalid email address'),
  phone:    z.string()
    .max(30, 'Phone too long')
    .regex(/^[+\d\s\-().]*$/, 'Invalid phone number')
    .optional()
    .or(z.literal('')),
  company:  z.string().max(150, 'Company name too long').optional().or(z.literal('')),
  bio:      z.string().max(1000, 'Bio must not exceed 1000 characters').optional().or(z.literal('')),
  address:  z.string().max(255).optional().or(z.literal('')),
  city:     z.string().max(100).optional().or(z.literal('')),
  country:  z.string().max(100).optional().or(z.literal('')),
  zip:      z.string().max(20).optional().or(z.literal('')),
});

export const changePasswordSchema = z.object({
  currentPassword: z.string().min(1, 'Current password is required'),
  newPassword:     z.string().min(8, 'New password must be at least 8 characters'),
  confirmPassword: z.string().min(1, 'Please confirm your new password'),
}).refine((d) => d.newPassword === d.confirmPassword, {
  message: 'Passwords do not match',
  path: ['confirmPassword'],
}).refine((d) => d.newPassword !== d.currentPassword, {
  message: 'New password must be different from your current password',
  path: ['newPassword'],
});

// ── SaaS ─────────────────────────────────────────────────────────────────────

export const projectSchema = z.object({
  name:         z.string().min(3, 'Project name must be at least 3 characters').max(200, 'Name too long'),
  client:       z.string().min(1, 'Please select a client'),
  developer:    z.string().optional().or(z.literal('')),
  status:       z.enum(['active', 'review', 'completed', 'paused'], {
    errorMap: () => ({ message: 'Please select a valid status' }),
  }),
  budget:       z.coerce.number().min(0, 'Budget cannot be negative').max(9999999, 'Budget too large').optional().or(z.literal('')),
  due:          z.string().optional().or(z.literal('')),
  description:  z.string().max(2000, 'Description too long').optional().or(z.literal('')),
});

export const taskSchema = z.object({
  title:       z.string().min(3, 'Task title must be at least 3 characters').max(300, 'Title too long'),
  project:     z.string().min(1, 'Please select a project'),
  priority:    z.enum(['Critical', 'High', 'Medium', 'Low'], {
    errorMap: () => ({ message: 'Please select a priority' }),
  }),
  assignee:    z.string().optional().or(z.literal('')),
  due:         z.string().optional().or(z.literal('')),
  description: z.string().max(5000, 'Description too long').optional().or(z.literal('')),
});

export const clientSchema = z.object({
  name:    z.string().min(2, 'Client name must be at least 2 characters').max(150, 'Name too long'),
  email:   z.string().min(1, 'Email is required').email('Invalid email address'),
  company: z.string().max(150, 'Company name too long').optional().or(z.literal('')),
  phone:   z.string()
    .max(30, 'Phone too long')
    .regex(/^[+\d\s\-().]*$/, 'Invalid phone number')
    .optional()
    .or(z.literal('')),
  address: z.string().max(300).optional().or(z.literal('')),
});

export const userSchema = z.object({
  name:     z.string().min(2, 'Name must be at least 2 characters').max(100, 'Name too long'),
  email:    z.string().min(1, 'Email is required').email('Invalid email address'),
  role:     z.enum(['SuperAdmin', 'Developer', 'Client'], {
    errorMap: () => ({ message: 'Please select a valid role' }),
  }),
  company:  z.string().max(150).optional().or(z.literal('')),
  password: z.string().min(8, 'Password must be at least 8 characters'),
});

// ── WP Ops ─────────────────────────────────────────────────────────────────

export const wpSiteSchema = z.object({
  name:               z.string().min(2, 'Site name must be at least 2 characters').max(200, 'Name too long'),
  clientId:           z.string().min(1, 'Please select a client'),
  url:                z.string()
    .min(1, 'Site URL is required')
    .url('Please enter a valid URL (include https://)'),
  wpAdminUrl:         z.string().url('Please enter a valid URL').optional().or(z.literal('')),
  hostingEnvironment: z.string().optional().or(z.literal('')),
  checkSchedule:      z.enum(['hourly', 'every_6h', 'daily', 'weekly']).optional(),
  priority:           z.enum(['low', 'normal', 'high']).optional(),
  notes:              z.string().max(2000, 'Notes must not exceed 2000 characters').optional().or(z.literal('')),
});

export const wpSiteAuthSchema = z.object({
  authMethod:   z.enum(['app_password', 'jwt', 'basic', 'none'], {
    errorMap: () => ({ message: 'Please select an auth method' }),
  }),
  authUsername: z.string().max(100, 'Username too long').optional().or(z.literal('')),
  authToken:    z.string().min(1, 'Access token is required'),
}).refine((d) => {
  if (d.authMethod === 'app_password') {
    const clean = d.authToken.replace(/\s/g, '');
    return clean.length === 24;
  }
  return true;
}, {
  message: 'WordPress Application Password must be exactly 24 characters (excluding spaces)',
  path: ['authToken'],
}).refine((d) => {
  if (d.authMethod === 'app_password') return !!d.authUsername?.trim();
  return true;
}, {
  message: 'Username is required for Application Password authentication',
  path: ['authUsername'],
});

export const wpClientSchema = z.object({
  name:  z.string().min(2, 'Client name must be at least 2 characters').max(150, 'Name too long'),
  slug:  z.string()
    .min(2, 'Slug must be at least 2 characters')
    .max(100, 'Slug too long')
    .regex(/^[a-z0-9_-]+$/, 'Slug may only contain lowercase letters, numbers, dashes, and underscores'),
  email: z.string().email('Invalid email address').optional().or(z.literal('')),
});

// ── Portal Settings (SuperAdmin) ──────────────────────────────────────────────

export const wordfenceKeySchema = z.object({
  api_key: z.string()
    .min(1, 'API key is required')
    .min(20, 'Wordfence API key appears too short')
    .max(500, 'API key too long')
    .regex(/^[A-Za-z0-9_\-]+$/, 'API key contains invalid characters'),
});

export const lighthouseKeySchema = z.object({
  api_key: z.string()
    .min(1, 'API key is required')
    .min(20, 'Google API key appears too short')
    .max(500, 'API key too long'),
});

export const aaSiteTokenSchema = z.object({
  aa_token: z.string()
    .min(10, 'Plugin token appears too short')
    .max(255, 'Plugin token too long'),
});
