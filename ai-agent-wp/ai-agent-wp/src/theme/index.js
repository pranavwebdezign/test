import { createTheme } from '@mui/material/styles';

const PJS = "'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";

const theme = createTheme({
  palette: {
    primary: {
      main: '#8E43F0',
      light: '#A96BF5',
      dark: '#6A1FCC',
      contrastText: '#ffffff',
    },
    secondary: {
      main: '#0099C2',
      light: '#33B4D4',
      dark: '#007DA6',
      contrastText: '#1A0A3C',
    },
    background: {
      default: '#F7F5FF',
      paper: '#ffffff',
    },
    text: {
      primary: '#1A0A3C',
      secondary: '#5C4A8A',
      disabled: '#9B89C4',
    },
    success: { main: '#1A9E6A', light: '#86EFAC', dark: '#16A34A' },
    warning: { main: '#B87200', light: '#FCD34D', dark: '#D97706' },
    error: { main: '#CC2222', light: '#FCA5A5', dark: '#DC2626' },
    info: { main: '#1A6FBB' },
    divider: '#DDD4F8',
    grey: {
      50: '#F7F5FF',
      100: '#EDE8FC',
      200: '#DDD4F8',
      300: '#C4B0EE',
      400: '#9B89C4',
      500: '#5C4A8A',
      600: '#4A3870',
      700: '#32235A',
      800: '#1E1040',
      900: '#1A0A3C',
    },
  },

  typography: {
    fontFamily: PJS,
    h1: { fontFamily: PJS, fontWeight: 800, fontSize: 'clamp(1.8rem, 4vw, 2.4rem)', lineHeight: 1.15, letterSpacing: '-0.025em' },
    h2: { fontFamily: PJS, fontWeight: 700, fontSize: 'clamp(1.4rem, 3vw, 1.9rem)', lineHeight: 1.2, letterSpacing: '-0.015em' },
    h3: { fontFamily: PJS, fontWeight: 700, fontSize: '1.25rem', lineHeight: 1.3, letterSpacing: '-0.01em' },
    h4: { fontFamily: PJS, fontWeight: 600, fontSize: '1.05rem', lineHeight: 1.35 },
    h5: { fontFamily: PJS, fontWeight: 600, fontSize: '0.95rem' },
    h6: { fontFamily: PJS, fontWeight: 600, fontSize: '0.88rem' },
    body1: { fontFamily: PJS, fontWeight: 400, fontSize: '1rem', lineHeight: 1.75 },
    body2: { fontFamily: PJS, fontWeight: 400, fontSize: '0.875rem', lineHeight: 1.65 },
    button: { fontFamily: PJS, fontWeight: 600, fontSize: '0.875rem', letterSpacing: '0.01em', textTransform: 'none' },
    caption: { fontFamily: PJS, fontWeight: 400, fontSize: '0.75rem', lineHeight: 1.5 },
    overline: { fontFamily: PJS, fontWeight: 700, fontSize: '0.7rem', letterSpacing: '0.12em', textTransform: 'uppercase' },
    subtitle1: { fontFamily: PJS, fontWeight: 500, fontSize: '0.95rem', lineHeight: 1.6, color: '#4A3870' },
    subtitle2: { fontFamily: PJS, fontWeight: 500, fontSize: '0.85rem', lineHeight: 1.57, color: '#5C4A8A' },
  },

  shape: { borderRadius: 10 },

  shadows: [
    'none',
    '0px 1px 2px rgba(142,67,240,0.06)',
    '0px 1px 4px rgba(142,67,240,0.08)',
    '0px 2px 8px rgba(142,67,240,0.08)',
    '0px 4px 12px rgba(142,67,240,0.08)',
    '0px 6px 16px rgba(142,67,240,0.10)',
    '0px 8px 24px rgba(142,67,240,0.10)',
    '0px 12px 32px rgba(142,67,240,0.12)',
    '0px 16px 40px rgba(142,67,240,0.12)',
    '0px 20px 48px rgba(142,67,240,0.14)',
    '0px 24px 56px rgba(142,67,240,0.14)',
    ...Array(14).fill('0px 24px 56px rgba(142,67,240,0.14)'),
  ],

  components: {
    // ── Global font reset via CssBaseline ──────────────────────────────────
    MuiCssBaseline: {
      styleOverrides: `
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body {
          font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
          -webkit-font-smoothing: antialiased;
          -moz-osx-font-smoothing: grayscale;
          font-feature-settings: 'cv02', 'cv03', 'cv04', 'cv11';
        }
        * { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; }
        input, textarea, select, button {
          font-family: 'Plus Jakarta Sans', sans-serif !important;
        }
      `,
    },

    // ── Layout ─────────────────────────────────────────────────────────────
    MuiAppBar: { styleOverrides: { root: { fontFamily: PJS } } },
    MuiToolbar: { styleOverrides: { root: { fontFamily: PJS } } },
    MuiDrawer: {
      styleOverrides: {
        paper: { fontFamily: PJS, border: 'none', boxShadow: '2px 0 20px rgba(142,67,240,0.10)' },
      },
    },

    // ── Buttons ────────────────────────────────────────────────────────────
    MuiButton: {
      styleOverrides: {
        root: {
          fontFamily: PJS,
          fontWeight: 600,
          fontSize: '0.875rem',
          textTransform: 'none',
          letterSpacing: '0.01em',
          borderRadius: 8,
          padding: '10px 20px',
          boxShadow: 'none',
          '&:hover': { boxShadow: '0 4px 12px rgba(142,67,240,0.30)' },
        },
        contained: { '&:hover': { boxShadow: '0 4px 12px rgba(142,67,240,0.40)' } },
        outlined: { borderWidth: '1.5px', '&:hover': { borderWidth: '1.5px' } },
      },
    },
    MuiIconButton: { styleOverrides: { root: { fontFamily: PJS } } },

    // ── Inputs ─────────────────────────────────────────────────────────────
    MuiInputBase: {
      styleOverrides: {
        root: { fontFamily: PJS, fontSize: '0.9rem', fontWeight: 400 },
        input: { fontFamily: PJS },
      },
    },
    MuiInputLabel: {
      styleOverrides: {
        root: { fontFamily: PJS, fontWeight: 500, fontSize: '0.875rem' },
      },
    },
    MuiTextField: {
      styleOverrides: {
        root: {
          fontFamily: PJS,
          '& .MuiOutlinedInput-root': {
            borderRadius: 8,
            '&:hover .MuiOutlinedInput-notchedOutline': { borderColor: '#8E43F0' },
          },
        },
      },
    },
    MuiSelect: {
      styleOverrides: { select: { fontFamily: PJS, fontWeight: 400 } },
    },
    MuiFormHelperText: {
      styleOverrides: { root: { fontFamily: PJS, fontSize: '0.72rem', fontWeight: 400 } },
    },

    // ── Menus & Lists ──────────────────────────────────────────────────────
    MuiMenuItem: {
      styleOverrides: { root: { fontFamily: PJS, fontSize: '0.875rem', fontWeight: 400 } },
    },
    MuiListItemText: {
      styleOverrides: {
        primary: { fontFamily: PJS, fontWeight: 500, fontSize: '0.875rem' },
        secondary: { fontFamily: PJS, fontSize: '0.78rem' },
      },
    },
    MuiListItemButton: {
      styleOverrides: {
        root: { fontFamily: PJS, borderRadius: 8, marginBottom: 2, '&.Mui-selected': { fontWeight: 600 } },
      },
    },

    // ── Cards & Surfaces ───────────────────────────────────────────────────
    MuiCard: {
      styleOverrides: {
        root: {
          borderRadius: 14,
          boxShadow: '0px 1px 4px rgba(142,67,240,0.08)',
          border: '1px solid #DDD4F8',
          '&:hover': { boxShadow: '0px 8px 24px rgba(142,67,240,0.12)' },
          transition: 'box-shadow 0.2s ease, transform 0.15s ease',
        },
      },
    },
    MuiCardContent: {
      styleOverrides: { root: { fontFamily: PJS } },
    },
    MuiPaper: {
      styleOverrides: {
        root: { backgroundImage: 'none' },
        elevation1: { boxShadow: '0px 1px 4px rgba(142,67,240,0.08)', border: '1px solid #DDD4F8' },
      },
    },

    // ── Data Display ───────────────────────────────────────────────────────
    MuiTableCell: {
      styleOverrides: {
        head: {
          fontFamily: PJS,
          fontWeight: 700,
          fontSize: '0.72rem',
          letterSpacing: '0.1em',
          textTransform: 'uppercase',
          color: '#5C4A8A',
          backgroundColor: '#EDE8FC',
        },
        body: { fontFamily: PJS, fontSize: '0.85rem', fontWeight: 400 },
      },
    },
    MuiChip: {
      styleOverrides: { root: { fontFamily: PJS, fontWeight: 600, fontSize: '0.75rem' } },
    },
    MuiBadge: {
      styleOverrides: { badge: { fontFamily: PJS, fontWeight: 700, fontSize: '0.68rem' } },
    },

    // ── Navigation ─────────────────────────────────────────────────────────
    MuiTab: {
      styleOverrides: {
        root: { fontFamily: PJS, fontWeight: 600, fontSize: '0.85rem', textTransform: 'none', letterSpacing: '0.01em' },
      },
    },
    MuiBreadcrumbs: {
      styleOverrides: { root: { fontFamily: PJS, fontSize: '0.82rem' } },
    },

    // ── Feedback ───────────────────────────────────────────────────────────
    MuiAlert: {
      styleOverrides: { root: { fontFamily: PJS, fontSize: '0.875rem', fontWeight: 500 } },
    },
    MuiTooltip: {
      styleOverrides: { tooltip: { fontFamily: PJS, fontSize: '0.75rem', fontWeight: 500 } },
    },
    MuiDialogTitle: {
      styleOverrides: { root: { fontFamily: PJS, fontWeight: 700, fontSize: '1.1rem' } },
    },
    MuiDialogContentText: {
      styleOverrides: { root: { fontFamily: PJS, fontSize: '0.9rem' } },
    },

    // ── Misc ───────────────────────────────────────────────────────────────
    MuiPaginationItem: {
      styleOverrides: { root: { fontFamily: PJS, fontWeight: 600, fontSize: '0.82rem' } },
    },
    MuiLinearProgress: {
      styleOverrides: { root: { borderRadius: 8, height: 6 } },
    },
    MuiAccordionSummary: {
      styleOverrides: { content: { fontFamily: PJS, fontWeight: 600 } },
    },
  },
});

export default theme;
