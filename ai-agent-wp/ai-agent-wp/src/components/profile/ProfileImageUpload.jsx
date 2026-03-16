import { useRef, useState, useCallback } from 'react';
import { Box, Typography, Avatar, IconButton, CircularProgress, Tooltip } from '@mui/material';
import { PhotoCameraOutlined, DeleteOutline } from '@mui/icons-material';

const MAX_BYTES = 2 * 1024 * 1024; // 2 MB
const ACCEPTED = ['image/jpeg', 'image/jpg', 'image/png'];

/**
 * ProfileImageUpload — click or drag-and-drop avatar upload.
 * Props: value (dataURL|null), onChange(dataURL|null), size (px)
 */
export default function ProfileImageUpload({ value, onChange, onFileSelected, size = 112, initials = '??' }) {
  const inputRef = useRef(null);
  const [dragging, setDragging] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const processFile = useCallback((file) => {
    if (!file) return;
    setError('');
    if (!ACCEPTED.includes(file.type)) { setError('Only JPG/PNG images are allowed.'); return; }
    if (file.size > MAX_BYTES) { setError('Image must be under 2 MB.'); return; }
    setLoading(true);
    // Pass raw File to parent for REST upload
    if (onFileSelected) onFileSelected(file);
    const reader = new FileReader();
    reader.onload = (e) => { onChange(e.target.result); setLoading(false); };
    reader.onerror = () => { setError('Failed to read file.'); setLoading(false); };
    reader.readAsDataURL(file);
  }, [onChange, onFileSelected]);

  const onFileInput = (e) => processFile(e.target.files?.[0]);
  const onDrop = (e) => { e.preventDefault(); setDragging(false); processFile(e.dataTransfer.files?.[0]); };
  const onDragOver = (e) => { e.preventDefault(); setDragging(true); };
  const onDragLeave = () => setDragging(false);

  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 1 }}>
      <Box
        sx={{ position: 'relative', cursor: 'pointer' }}
        onDrop={onDrop}
        onDragOver={onDragOver}
        onDragLeave={onDragLeave}
        onClick={() => !loading && inputRef.current?.click()}
      >
        <Avatar
          src={value || undefined}
          sx={{
            width: size, height: size,
            fontSize: size * 0.3,
            fontWeight: 700,
            bgcolor: dragging ? '#D4B8FA' : 'primary.main',
            border: dragging ? '3px dashed #8E43F0' : '3px solid #DDD4F8',
            transition: 'all 0.2s ease',
            boxShadow: dragging ? '0 0 0 4px rgba(142,67,240,0.15)' : 'none',
          }}
        >
          {!value && (loading ? null : initials)}
        </Avatar>

        {loading && (
          <Box sx={{ position: 'absolute', inset: 0, display: 'flex', alignItems: 'center', justifyContent: 'center', borderRadius: '50%', bgcolor: 'rgba(0,0,0,0.4)' }}>
            <CircularProgress size={28} sx={{ color: '#fff' }} />
          </Box>
        )}

        {/* Camera overlay */}
        {!loading && (
          <Box sx={{
            position: 'absolute', inset: 0, borderRadius: '50%',
            bgcolor: 'rgba(0,0,0,0.38)', display: 'flex', alignItems: 'center', justifyContent: 'center',
            opacity: 0, transition: 'opacity 0.2s',
            '&:hover': { opacity: 1 },
          }}>
            <PhotoCameraOutlined sx={{ color: '#fff', fontSize: 26 }} />
          </Box>
        )}

        {/* Remove button */}
        {value && !loading && (
          <Tooltip title="Remove photo">
            <IconButton
              size="small"
              onClick={(e) => { e.stopPropagation(); onChange(null); }}
              sx={{ position: 'absolute', bottom: 0, right: 0, bgcolor: '#EF4444', color: '#fff', '&:hover': { bgcolor: '#DC2626' }, width: 26, height: 26 }}
            >
              <DeleteOutline sx={{ fontSize: 14 }} />
            </IconButton>
          </Tooltip>
        )}
      </Box>

      <input ref={inputRef} type="file" accept={ACCEPTED.join(',')} style={{ display: 'none' }} onChange={onFileInput} />

      <Typography variant="caption" color="text.secondary" sx={{ textAlign: 'center', lineHeight: 1.4 }}>
        Click or drag & drop to upload<br />JPG / PNG · max 2 MB
      </Typography>
      {error && <Typography variant="caption" color="error.main">{error}</Typography>}
    </Box>
  );
}
