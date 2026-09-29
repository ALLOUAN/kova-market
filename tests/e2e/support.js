import crypto from 'node:crypto';

/** Authenticator secret of the seeded super-admin (tests/e2e/global-setup.js). */
export const ADMIN_TOTP_SECRET = 'JBSWY3DPEHPK3PXP';

/**
 * The current 6-digit code of an authenticator app (RFC 6238: SHA-1, 30 s steps), as the phone would show it.
 */
export function totp(secret, now = Date.now()) {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    const bits = [...secret.replace(/=+$/, '')].map((c) => alphabet.indexOf(c).toString(2).padStart(5, '0')).join('');
    const key = Buffer.from(bits.match(/.{8}/g).map((byte) => parseInt(byte, 2)));

    const counter = Buffer.alloc(8);
    counter.writeBigUInt64BE(BigInt(Math.floor(now / 30000)));

    const hmac = crypto.createHmac('sha1', key).update(counter).digest();
    const offset = hmac[hmac.length - 1] & 0xf;

    return String((hmac.readUInt32BE(offset) & 0x7fffffff) % 1_000_000).padStart(6, '0');
}
