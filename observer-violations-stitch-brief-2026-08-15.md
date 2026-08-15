# Observer Violations Public Form

Date: 2026-08-15

Purpose:
Design a public-facing, mobile-friendly multi-step submission page for reporting observer-documented voter intimidation or harassment in Osun State.

This is not a dashboard and not a newsroom article page. It is a guided public form experience for field submission. The page must feel credible, calm, civic, and secure.

## Page Goal

Convert the current single long form into a three-step guided experience:

1. Observer Profile
2. Incident Location
3. Evidence

The form should reduce stress for mobile users in the field, keep the interaction focused, and make progress visible at all times.

## Visual Direction

Use Dataphyte brand colours as the core system:

- Dataphyte Blue: `#2f70a8`
- Dataphyte Light Blue: `#4fa6de`
- Dataphyte Red: `#b52408`

Supporting neutrals:

- Deep text: `#183247`
- Muted text: `#5d7283`
- Soft background: `#f5f8fb`
- White surface: `#ffffff`
- Line/border: `#d6e2ec`
- Success: `#1f7a52`
- Error: `#a1261a`

Tone:

- Professional, civic, factual
- Clean and modern, but not startup-generic
- More institutional trust than editorial drama
- Strong hierarchy, minimal decoration

## Typography

Recommended system for Stitch:

- Headings: Montserrat or a similarly firm geometric sans
- Body/UI text: Source Sans 3, Inter, or a neutral humanist sans

Typography behaviour:

- Large compact headline in the hero
- Clear section titles for each step
- Small helper text under labels where needed
- Button text and step labels should be bold and highly legible

## Page Structure

### 1. Hero band

Top section with:

- small eyebrow such as `Observer Violation Intake`
- clear page title such as `Report Voter Intimidation or Harassment`
- short explanatory paragraph
- a trust note that submissions are handled securely and evidence is not public

Layout:

- full-width hero panel inside a centred content container
- blue-led visual identity with a restrained red accent
- subtle gradient or layered geometric background, not flat colour

### 2. Progress stepper

Directly below the hero, add a three-step progress bar:

- Step 1: Observer Profile
- Step 2: Incident Location
- Step 3: Evidence

Behaviour:

- current step highlighted in Dataphyte Blue
- completed steps marked clearly
- future steps visible but muted
- stepper remains visible near the top on desktop
- on mobile it can collapse into `Step 1 of 3` plus a slim progress indicator

### 3. Form card area

Main form sits inside a clean white card with generous padding.

Each step should show only the fields relevant to that step.

Navigation:

- `Continue` button at the bottom right
- `Back` button from step 2 onward
- final step uses `Submit Report`

Validation:

- validate per step before moving forward
- keep inline errors close to fields
- show an error summary only if necessary

## Step-by-Step Content

### Step 1: Observer Profile

Purpose:
Collect enough identity and assignment information to establish source credibility.

Fields:

- Full name
- Phone number
- Email address
- Organisation
- Observer ID or deployment code
- Role
- Verification status
- Assigned state
- Assigned LGA
- Assigned ward
- Assigned polling unit
- Assigned polling unit name

Layout:

- two-column grid on desktop
- one-column stack on mobile
- assignment fields grouped visually under a subheading like `Deployment Details`

Design note:

- This step should feel straightforward and calm
- Use light-blue tinted helper panels for explanatory notes if needed

### Step 2: Incident Location

Purpose:
Capture exact where-and-when details of the reported violation.

Fields:

- Incident state
- Incident LGA
- Incident ward
- Incident polling unit
- Incident polling unit name
- Address or landmark
- GPS latitude
- GPS longitude
- Incident date
- Time observed
- Incident still ongoing
- Violation category
- Incident description

Layout:

- location selector fields first
- time/date and ongoing state next
- long description field last as a full-width block

Design note:

- Make the location cascade feel guided
- The polling unit selector should feel like a controlled lookup, not free typing
- Use subtle dividers between `Where`, `When`, and `What happened`

### Step 3: Evidence

Purpose:
Collect supporting material while reinforcing privacy and consent.

Fields:

- Evidence description
- Witness statement
- External reference URL
- Evidence files
- Evidence consent confirmation

Layout:

- lead with a short privacy/security note in a bordered information panel
- file upload area should be a clear dropzone-style block or obvious upload zone
- consent confirmation should sit in a strong highlighted container near submission

Design note:

- This step should visually signal care and seriousness
- Use Dataphyte Red sparingly for caution language, not as the dominant colour

## Key Components

Create these reusable components in the design system:

- Hero panel
- Stepper / progress indicator
- Primary button
- Secondary button
- Text input
- Select input
- Textarea
- File upload zone
- Inline helper text
- Inline error state
- Information panel
- Consent / confirmation block
- Success state panel

## Interaction Behaviour

- Step change should feel fast and clean, not playful
- Use a subtle horizontal slide or fade between steps
- Keep motion restrained and short
- Preserve entered values when navigating back

## Mobile Behaviour

This page is primarily mobile-first.

Requirements:

- single-column form layout on small screens
- sticky bottom action bar for `Back` and `Continue` / `Submit`
- generous tap targets
- short labels and clear spacing
- progress indication always visible

Avoid:

- tiny helper text
- crowded two-column mobile layouts
- decorative graphics that push the form too far below the fold

## Desktop Behaviour

- centred content column with comfortable max width
- hero, stepper, and form card stacked vertically
- enough whitespace for clarity, but not excessive dead space
- desktop can use two-column field groupings where helpful

## Content Tone

Copy should feel:

- formal
- reassuring
- clear
- secure
- direct

Avoid:

- sensational wording
- activist-poster visual language
- newsroom-like article layout
- dark, hostile, or alarming mood

## Specific Avoidances

- Do not design this like a donation page
- Do not design this like a generic SaaS onboarding wizard
- Do not mix in newsletter or campaign visuals
- Do not use purple
- Do not use rounded playful pills everywhere
- Do not make red the dominant background colour

## Success State

After submission, show a calm confirmation state with:

- clear success title
- short message confirming secure receipt
- report reference ID
- note that status is `submitted`

Style:

- white success panel with blue structure and green confirmation accent

## Stitch Prompt Starter

Design a mobile-first public multi-step civic reporting form for Dataphyte. Use Dataphyte Blue `#2f70a8`, Dataphyte Light Blue `#4fa6de`, and Dataphyte Red `#b52408`. The page has a hero, a 3-step progress indicator, and a white card form split into Observer Profile, Incident Location, and Evidence. The tone is credible, secure, calm, and institutional, with strong form hierarchy, light blue helper surfaces, restrained red caution accents, and clean modern typography. Prioritise mobile usability, sticky actions, clear validation states, and a serious public-interest visual language.
