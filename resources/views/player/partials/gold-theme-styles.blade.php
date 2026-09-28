{{-- Shared black & gold styling for player-facing pages built to the gold
     mockup (Wallet, Transactions). Loads once per page via @include, not
     per-component, since Blade's <style> blocks aren't deduplicated. --}}
<style>
    .gold-serif{font-family:'Cormorant Garamond',Georgia,serif}
    .gold-ornament{display:flex;align-items:center;justify-content:center;gap:8px;margin:0 0 6px}
    .gold-ornament .line{height:1px;width:22px;background:linear-gradient(90deg,transparent,#a8791f)}
    .gold-ornament .line.r{background:linear-gradient(90deg,#a8791f,transparent)}
    .gold-ornament .diamond{width:5px;height:5px;background:#a8791f;transform:rotate(45deg)}
    .gold-eyebrow{font-size:11px;font-weight:600;letter-spacing:.28em;color:#c9a04a;margin:0 0 2px;text-align:center;text-transform:uppercase}
    .gold-h1{font-size:28px;font-weight:600;letter-spacing:.02em;margin:2px 0 18px;text-align:center;color:#f4efe4}
    .gold-card{position:relative;border-radius:14px;padding:22px 20px;background:linear-gradient(180deg,#0e0e10,#0b0b0c);border:1px solid rgba(168,121,31,.34)}
    .gold-card::before{content:"";position:absolute;top:0;left:14%;right:14%;height:1px;background:linear-gradient(90deg,transparent,#a8791f,transparent);opacity:.8}
    .gold-label{font-size:10px;font-weight:600;letter-spacing:.22em;color:#9c8f7b;text-align:center;text-transform:uppercase;margin:0 0 8px}
    .gold-balance{font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:700;text-align:center;margin:0 0 18px;letter-spacing:.01em;
        background:linear-gradient(180deg,#c9a04a,#a8791f 60%,#5e4517);-webkit-background-clip:text;background-clip:text;color:transparent}

    /* Primary CTA button — framed metallic-gold border with corner accents.
       Sized for a real button (~40px tall), not the zoomed mockup artboard;
       .inline switches it from a full-width block to shrink-to-content for
       secondary/inline placements (Back links, etc). */
    .gold-btn-frame{position:relative;display:block;width:100%;overflow:hidden;padding:1.5px;border-radius:8px;background:linear-gradient(155deg,#fdf0c8 0%,#e8c15f 22%,#a8791f 48%,#f0cf7e 68%,#7a591c 100%);box-shadow:0 4px 10px -6px rgba(0,0,0,.6);text-decoration:none;cursor:pointer;border:none;font:inherit}
    .gold-btn-frame.inline{width:auto;display:inline-block}
    .gold-btn-frame:disabled,.gold-btn-frame[disabled]{opacity:.3;cursor:not-allowed}
    .gold-btn-corner{position:absolute;width:3px;height:3px;background:#fff3d6;box-shadow:0 0 2px 0 rgba(255,243,214,.8)}
    .gold-btn-corner.tl{top:1px;left:1px} .gold-btn-corner.tr{top:1px;right:1px}
    .gold-btn-corner.bl{bottom:1px;left:1px} .gold-btn-corner.br{bottom:1px;right:1px}
    .gold-btn-fill{position:relative;display:block;overflow:hidden;border-radius:6.5px;padding:9px 16px;text-align:center;font-weight:800;font-size:11px;letter-spacing:.08em;text-transform:uppercase}
    .gold-btn-fill::before{content:"";position:absolute;inset:0;background:linear-gradient(115deg,transparent 38%,rgba(255,255,255,.28) 50%,rgba(255,255,255,.03) 60%,transparent 70%);pointer-events:none}
    .gold-btn-primary .gold-btn-fill{background:linear-gradient(180deg,#f2d68e,#c9a04a 45%,#8a611a 100%);color:#241600;box-shadow:inset 0 1px 0 rgba(255,255,255,.5),inset 0 -5px 8px -6px rgba(50,32,0,.5);text-shadow:0 1px 0 rgba(255,255,255,.3)}
    .gold-btn-secondary .gold-btn-fill{background:linear-gradient(180deg,#2c271f,#141210 55%,#0a0806);color:#c9a04a;box-shadow:inset 0 1px 0 rgba(255,255,255,.08),inset 0 -4px 6px -6px rgba(0,0,0,.75)}

    /* Small inline utility buttons/links (Max, View request, Cancel, Back
       to…) — a plain gold-outlined pill rather than the framed/corner-dot
       treatment above, so minor actions don't compete with primary CTAs. */
    .gold-chip-btn{display:inline-flex;align-items:center;justify-content:center;gap:4px;border-radius:7px;border:1px solid rgba(168,121,31,.4);background:rgba(168,121,31,.08);color:#c9a04a;font-weight:700;font-size:11px;letter-spacing:.04em;padding:7px 12px;text-decoration:none;cursor:pointer;transition:background .15s,border-color .15s}
    .gold-chip-btn:hover{background:rgba(168,121,31,.16);border-color:rgba(168,121,31,.6)}

    /* Gold hairline border for cards/panels, in place of the plain
       near-black border those used before. */
    .gold-panel{background:#0a0e16;border:1px solid rgba(168,121,31,.22);border-radius:.75rem}

    .gold-section-title{font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#9c8f7b;margin:0 0 10px;padding:0 2px}
    .gold-tabs{display:flex;gap:18px;padding:0 4px 10px;margin-bottom:2px;border-bottom:1px solid rgba(255,255,255,.06);overflow-x:auto}
    .gold-tab{font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#6d6252;padding-bottom:9px;position:relative;white-space:nowrap;background:none;border:none;cursor:pointer}
    .gold-tab.is-active{color:#c9a04a;font-weight:700}
    .gold-tab.is-active::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;border-radius:1px;background:linear-gradient(90deg,#c9a04a,#a8791f);box-shadow:0 0 6px 0 rgba(168,121,31,.6)}

    .gold-filter-input{background:#0b0b0c;border:1px solid rgba(168,121,31,.2);color:#f4efe4}
    .gold-filter-input:focus{outline:none;border-color:rgba(168,121,31,.5);box-shadow:0 0 0 2px rgba(168,121,31,.25)}
    .gold-filter-btn{background:linear-gradient(180deg,#c9a04a,#a8791f);color:#241600;border:none;border-radius:8px;font-weight:800;font-size:11px;letter-spacing:.06em;padding:7px 14px;text-transform:uppercase}
</style>
