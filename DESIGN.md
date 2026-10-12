# DISIPLIN PRO — OFFICIAL DESIGN SPECIFICATION

This document serves as the official design specification and component standard for **DISIPLIN PRO**, a school student-discipline administration SaaS.

Any future UI implementation must strictly adhere to the guidelines defined in this document to ensure a consistent, premium, and professional user experience.

---

## 1. PRODUCT VISUAL IDENTITY

**DISIPLIN PRO** is designed for school administrators, counseling staff (BK), and teachers. 

**Core Visual Direction:**
- **Premium, Clean, Professional SaaS:** The UI must feel like a modern, high-quality enterprise tool, not a generic admin template.
- **Calm & Structured:** Suitable for daily administrative work. Heavy usage of negative space (whitespace) prevents cognitive overload.
- **Color Identity:** Light neutral surfaces (cool-gray/lavender background) with white elevated surfaces and restrained Indigo (`#6366F1`) accents for primary interactions.
- **Anti-Slop Guidelines:** Avoid generic AI-generated aesthetics, excessive multi-color gradients, heavy shadows, unnecessary glassmorphism, or purely decorative elements that do not serve a functional purpose.

---

## 2. DESIGN TOKENS

The following tokens are the established standard for DISIPLIN PRO. **Do not introduce new hex codes if an existing semantic token applies.**

### Colors
- **Application Background:** `bg-[#F8F9FA]` (Soft cool-gray).
- **Primary Surfaces (Cards/Containers):** `bg-white`.
- **Primary Interaction (Indigo):** 
  - Base: `bg-[#6366F1]`
  - Hover: `hover:bg-[#4F46E5]`
  - Shadow (Glow): `shadow-[0_4px_14px_#6366F140]`
- **Text Hierarchy:**
  - Headings & Strong Data: `text-gray-900`
  - Secondary/Body: `text-gray-700`
  - Muted/Descriptions: `text-gray-500` or `text-gray-400`
- **Borders & Dividers:** `border-gray-50` (soft table borders) to `border-gray-200` (input borders).
- **Semantic Status Colors:**
  - **Success / Aman:** `bg-green-50 text-green-600`
  - **Warning / Perhatian:** `bg-yellow-50 text-yellow-600`
  - **Alert / Waspada:** `bg-orange-50 text-orange-600`
  - **Danger / Kritis / Pelanggaran:** `bg-red-50 text-red-600`
  - **Info / Netral:** `bg-gray-50 text-gray-600`

### Typography & Spacing
- **Font Family:** `Outfit` (sans-serif), configured in Tailwind CSS as `font-sans`.
- **Border Radius Scale:**
  - Cards / Main Containers: `rounded-[24px]`
  - Buttons / Inputs: `rounded-full` (pill shape) for primary actions, `rounded-xl` or `rounded-lg` for secondary elements.
  - Status Tags: `rounded-md`.
- **Shadow Levels:**
  - Main Cards: `shadow-[0_2px_20px_#00000006]` (very soft, ambient shadow).
  - Elevated Inputs/Toolbars: `shadow-sm`.

---

## 3. APPLICATION SHELL

The application wrapper `AuthenticatedLayout.vue` defines the standard layout.

- **Sidebar Structure:** Fixed `w-[260px]` width on desktop. Grouped navigation using uppercase tracking-widest labels (`text-[11px] font-bold text-gray-400`).
- **Navigation States:**
  - Active: `bg-gray-100 text-gray-900 font-bold`.
  - Inactive: `text-gray-500 font-medium hover:bg-gray-50 hover:text-gray-900`.
- **Header:** Transparent background, `h-20`, containing breadcrumbs/title on the left and utility actions (search, notifications) on the right.
- **Main Content Area:** Max-width constrained to `max-w-[1400px]` for optimal readability on ultra-wide monitors, with appropriate responsive padding (`px-4 sm:px-8`).

---

## 4. COMPONENT STANDARDS

**PrimeVue 4 (Aura Theme)** is the foundational component library. 

- **Primary Actions:** Links or buttons that trigger creation (e.g., "Tambah Siswa") use the pill-shaped Indigo token: `px-5 py-2.5 rounded-full bg-[#6366F1] text-white text-sm font-semibold`.
- **Secondary Actions:** White or gray backgrounds with borders: `rounded-full border border-gray-200 bg-white text-gray-700`.
- **Status Tags:** Use native HTML with flexbox `inline-flex px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider` and semantic background colors. Do not use PrimeVue `Tag` if it restricts this styling.
- **Inputs:** `h-10` or `py-2.5`, `rounded-full`, `bg-gray-50 border-gray-200 focus:ring-[#6366F1]`.
- **Flash Messages (Toasts & Inline):** Displayed using PrimeVue `Toast` globally, or as inline semantic banners (`bg-green-50 text-green-600 rounded-[16px] px-5 py-4`).

---

## 5. TABLE DESIGN RULES

Tables (`DataTable` via PrimeVue) are a critical part of DISIPLIN PRO. They must follow a **borderless list aesthetic**.

**Global Table Styling (via global CSS or PT props):**
- **Header (`thead th`):** Transparent background, `pb-4`, `border-b border-gray-50`. Text must be `text-xs font-semibold text-gray-400 uppercase tracking-wider`.
- **Body (`tbody td`):** `py-4 border-b border-gray-50`. No vertical borders. No background colors except `hover:bg-gray-50` on the row (`tr`).
- **Paddings:** The leftmost and rightmost columns must remove padding (`!pl-0` and `!pr-0`) to align flush with the card container, or use padding explicitly defined by the layout.

**Explicit Alignment Specification (Mandatory for `Students/Index.vue`):**
Alignment MUST be explicitly declared using PrimeVue's `headerStyle` and `bodyStyle` API on the `<Column>` component. **Never use negative margins or transforms to center table content.**

| Column | Header Alignment (`headerStyle`) | Body Alignment (`bodyStyle`) |
| :--- | :--- | :--- |
| **NIS** | `text-align: left;` | `text-align: left;` |
| **Siswa** | `text-align: left;` | `text-align: left;` |
| **Kelas** | `text-align: left;` | `text-align: left;` |
| **Poin** | `text-align: center;` | `text-align: center;` |
| **Status** | `text-align: center;` | `text-align: center;` |
| **Aksi** | `text-align: center;` | `text-align: center;` |

*(Note: In the `Aksi` column, the buttons themselves must be grouped using `flex justify-center` so the entire action group is centered under the header).*

---

## 6. PAGE PATTERNS

### A. Index / List Pages
- **Header:** `text-[28px] font-bold text-gray-900` with a `text-[15px] text-gray-500` subtitle.
- **Toolbar:** Placed directly inside the white card above the table. Contains a search input (with a left-aligned search icon) and filter dropdowns.
- **Card Wrapper:** The toolbar, table, and pagination are enclosed in a single `bg-white rounded-[24px] p-7 shadow-[...]` card.

### B. Dashboard
- **KPI Metrics:** Rendered as a grid of `rounded-[24px]` cards containing a title, icon, large numeric value (`text-[34px] font-bold`), and a semantic trend badge.
- **Split Layouts:** Utilize CSS grid (`grid-cols-1 lg:grid-cols-2`) for placing tables side-by-side (e.g., Recent Activity).

### C. Forms (Create/Edit)
- Fields must be stacked clearly. Validation errors rendered below the field in red text (`text-xs text-red-500 mt-1`).
- Submit buttons placed at the bottom or top right, matching the Primary Action token.

---

## 7. RESPONSIVE & ACCESSIBILITY RULES

- **Mobile Sidebar:** Transitions to an off-canvas drawer triggered by a hamburger menu in the header. Includes a dark backdrop overlay (`bg-gray-900/50`).
- **Table Overflow:** PrimeVue DataTables must utilize `responsiveLayout="stack" breakpoint="640px"` or horizontal scrolling (`overflow-x-auto`) for mobile views to prevent breaking the layout.
- **Buttons & Icons:** Any icon-only button (like Edit/Delete) must contain an accessible `title` attribute or `aria-label`.

---

## 8. PRIMEVUE & TAILWIND INTEGRATION RULES

- **Tailwind CSS 4:** Used as the primary layout and utility engine.
- **PrimeVue Pass-Through (PT):** Use the `:pt` prop to strip out default PrimeVue styling that conflicts with the design system (e.g., `table: { class: 'border-none' }`).
- **Global CSS:** Global overrides (like `.p-datatable-thead > tr > th`) must be placed in `<style>` blocks or `app.css` carefully, ensuring they do not bleed into unrelated modules. Use them only when `pt` or `headerClass` is insufficient.

---

## 9. DESIGN GOVERNANCE CHECKLIST

Before submitting new UI code, verify:

1. [ ] Did you read `DESIGN.md`?
2. [ ] Is the main card using `rounded-[24px]` and the correct shadow token?
3. [ ] Are primary action buttons pill-shaped (`rounded-full`) and Indigo (`#6366F1`)?
4. [ ] Did you explicitly set `headerStyle` and `bodyStyle` alignments for your `DataTable` `<Column>` components?
5. [ ] Is the search/filter toolbar perfectly aligned and responsive?
6. [ ] Did you run `npm run build` and verify the UI physically in the browser across Desktop and Mobile dimensions? 

*A page is not visually complete merely because the build passes. It must look premium and intentional.*
