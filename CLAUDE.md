# CLAUDE.md — KOŞAR E-Commerce Frontend Rules

## Always Do First

* Always analyze the existing Laravel frontend structure before making UI changes.
* Work component-by-component. Do not redesign the entire site at once.
* Focus only on frontend presentation unless explicitly requested otherwise.

---

# Project Context

This project is a custom Laravel e-commerce platform for KOŞAR.

Product categories include:

* Industrial fans
* Hydrofor systems
* Water pumps
* Submersible pumps
* Technical industrial equipment

The brand identity must feel:

* Premium
* Corporate
* Trustworthy
* Clean
* Modern
* Fast
* Mobile-first

This is NOT a SaaS dashboard or startup landing page.

---

# Design Direction

Reference feeling:

* Apple → spacing and clarity
* Bosch Professional → technical trust
* Schneider Electric → industrial professionalism
* Dyson → premium product presentation

The interface must stay:

* Bright
* Airy
* White/light-gray based
* Easy to scan
* Conversion-focused

Never create a dark UI.

---

# Brand Colors

Primary brand color:

* Deep navy / dark blue

Base surfaces:

* White
* Soft gray
* Very light blue-gray

Avoid:

* Red
* Orange
* Purple
* Neon colors
* Heavy gradients

---

# Frontend Rules

## Layout

* Use clean container widths
* Maintain consistent spacing system
* Prefer large breathing spaces
* Use modern card layouts

## Typography

* Clean modern typography
* Highly readable product information
* Strong hierarchy
* Avoid over-stylized fonts

## Buttons

* Premium CTA buttons
* Strong hover states
* Soft transitions only
* Never use `transition-all`

## Shadows

* Use soft layered shadows
* Avoid harsh dark shadows

## Animations

Allowed:

* opacity
* transform
* subtle scale
* soft hover movement

Not allowed:

* heavy motion
* parallax overload
* animation libraries
* flashy effects

Always keep performance high.

---

# Technical Rules

Do NOT modify:

* Backend logic
* Routes
* Controllers
* Database
* Migrations
* Cart flow
* Checkout flow
* Authentication
* Product logic

Do NOT break:

* SEO structure
* URL structure
* Existing Blade architecture

Allowed:

* Blade UI improvements
* Tailwind/CSS improvements
* Small Alpine.js interactions
* Responsive improvements

---

# Priority Areas

Improve these first:

1. Header
2. Hero section
3. Product cards
4. Category pages
5. Product detail top section
6. CTA sections
7. Footer
8. Mobile experience

---

# Quality Standard

The final result must feel:

* Premium
* Fast
* Corporate
* Industrial
* Trustworthy
* Modern
* Clean

Avoid generic Tailwind UI appearance.
