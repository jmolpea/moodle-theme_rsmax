# Changes

## 1.4.3 (2026-11-15)

- Fixed: the presentation of a hidden or locked module was sent inside the course page, and its files were served, to people who could not open that module. Only the presentations of the modules a person may open are sent now; teachers still see them all.
- Fixed: the banner uploaded for a course was not shown on the enrolment page to people who are not in the course yet. It is served the way Moodle serves the picture of a course.
- Fixed: the one-line fields of the presentation of a module say when a text is too long, instead of failing when saved.
- Fixed: the form of the presentation of a module failed to open with «Undefined constant EDITOR_UNLIMITED_FILES».
- Adding an area asks for the permission to add a «Text and media area» too.
- A colour setting only takes three, six or eight hexadecimal digits.
- A backup merged into an existing course no longer replaces where its banner is shown or the look of its pages.
- The description of «Show progress by section» says it needs the course progress, and the one of the palettes how to apply the same palette again.
- A link to the configuration manual at the top of the theme settings and of the banners page of a course.

## 1.4.2 (2026-11-14)

- Fixed: the banners of the sections were not shown in course formats without the wrapper of the section, such as tabs (Onetopic) or one section per page. Only the banner of the course was seen.
- «Add submission» on an assignment opens the page at the submission form, instead of at the top of the description.

## 1.4.1 (2026-11-13)

- A site that upgrades from a version before 1.4 keeps the look of its courses: the classroom stays off until the administrator turns it on, in the Courses tab of the theme settings. A new site has it on.
- Fixed: rubric tables in the content of a course (`rsx-table--rubric`) fit the width of the page. The second column was kept on one line, so a table with long descriptors grew wider than the page and its last columns could not be read. The criterion takes 22% of the width, the levels share the rest and long words wrap.
- An activity with the tag `progreso:no` or `progress:no` does not count as a step of its module. It is for an activity that only some learners complete, such as the choice of a path: without the tag the module never showed as completed for the others.
- The button that holds the course tabs that do not fit in one line is translated: its label was in Spanish on every site.
- The label of the assistant's suggested questions and the name of the captions track of a video are translated: they were in Spanish on every site.
- The header of a course with a banner lets more of the image through.
- New piece for the content of a page: `rsx-materials`, a box of links to the files of a lesson or a module.
- The two icons of the classroom are files of the theme instead of images written inside the stylesheet.
- The code of the classroom follows Moodle's coding style and its JavaScript is built with Moodle's own tools.

## 1.4.0 (2026-11-05)

- RSMAX classroom: the design of the theme inside every course. Light pages, the «your next step» header with the map of modules, areas, the presentation of each module and the type and duration of each activity. It is a setting of the theme (Courses tab), on in a new site. The choice between stage and light pages in «Banners» is only offered when the classroom is off.
- The «your next step» header and the map of modules work with any number of modules: every section but the general one. A module shows its code when its name starts with one («M1 · …»), and its number otherwise.
- Presentation of the module: a photo or a video (a file, YouTube or Vimeo, or a «video coming soon» box), a line above the text, the text, an optional full text and the card «About this module». It is filled in with fields from «Add presentation of the module», in edit mode, and is shown at the top of the module with the same design in every course. A video file can go with its subtitles in a `.vtt` file. The presentation travels in course backups and is deleted with its course or section.
- Type and duration of each activity. The type comes from the kind of activity (a page is a reading, an assignment is assessed, a quiz with no grade is practice) and can be changed with a tag on the activity: `tipo:hito` or `type:milestone`. The duration is a tag too: `duracion:30min`, `duration:1.5h`.
- For learners, in every course: the tabs «Course» and «My submissions», the panel of the submission on assignments, suggested questions for the AI assistant and long briefs that open with «Read more».
- The styles of the classroom are in `scss/aula.scss` and `scss/aula-sitio.scss`.

## 1.3.0 (2026-11-04)

- Each course chooses the look of its pages in «Banners»: stage, with a dark panel at the top of every section and activity, or light, with headers, the course index and the bar between pages on the paper of the page.
- The header of the course home follows the module on screen: its title, its progress and its next step. A completed module points to the next one and a locked module says what opens it.
- Map of modules in the header of the course: one cell for each module with its state (completed, in progress, not started or locked) and how many modules are completed.
- Areas: an area groups the activities placed under it, up to the next area, under a heading that folds. «Add area» is on every module in edit mode, the area is dragged like any activity and it is shown as a subheading in the course index. An area is a «Text and media area», so it changes neither sections, restrictions nor completion.
- When the tabs of a course do not fit in one line, the ones left over open from a button at the end of the line instead of wrapping.

## 1.2.3 (2026-10-13)

- The theme settings say where the light and dark colour modes are switched on.

## 1.2.2 (2026-10-13)

- The content of pages, books, lessons and wikis takes the whole width of the column, instead of a narrow reading measure that cut pages with tables, cards or video in half.

## 1.2.1 (2026-10-13)

- On a wide window the side panels of the site home reach the edge of the window: the panel stopped short of it and left an empty strip beside it.

## 1.2.0 (2026-10-13)

- Ready-made palettes change the whole character of the site: besides the four main colours, each one sets the colour of the course and activity headers, of the home hero, of the course index and of the footer, and the style of the menu and of the footer. Three new palettes (Ocean, Sand and Graphite): eight in all.
- Fixed: a palette chosen in the settings was saved but its colours were not applied.
- New setting: on a dark or brand coloured menu the logo is drawn in white, so a logo made for a light page can still be read.
- The logos and the icon of the site (favicon) can be changed from the Header tab of the theme settings. They are Moodle's own settings.
- The header of an activity and of a section starts at the same height as the header of the course, and is as wide.
- The open tab of the site administration and the open page of the phone menu are readable with a light brand colour.
- On narrow phones the completion button of the bar that moves between activities no longer runs under the button that goes on.
- On the site home the side panels are anchored to the window, from the menu to the bottom.

## 1.1.0 (2026-10-08)

- The site home opens with the name of the site, a search box for its courses and a button to the catalogue, followed by what the site shows on its home page. The title and the line under it are settings, and the opening can be turned off.
- The theme needs no other plugin.

## 1.0.2 (2026-10-08)

- The buttons that open the side panels touch the content they open; on the home page they hang from the edges of the window.
- The open page of the course menu keeps its colour when the link has been visited.

## 1.0.1 (2026-10-08)

- The course page is wider (1280 px by default) and tighter: the course header starts right under the navigation bar and the course menu sits close to it and to the sections.
- The side panels stay beside the content when they are open.
- A course without a picture keeps the background of the theme in its header, instead of a generated pattern sent inside every page.

## 1.0.0 (2026-10-08)

First release, for Moodle 5.3.
