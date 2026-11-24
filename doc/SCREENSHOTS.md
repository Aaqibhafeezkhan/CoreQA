# CoreQA Screenshot Guide

Capture screenshots only after a working demo has been deployed or started locally with representative data. Do not add fabricated interface screenshots.

## Required screenshots

Save the following PNG files in \`doc/images/\`.

| File | Dimensions | Content |
| --- | --- | --- |
| \`question-list.png\` | 1280x800 | Home page with recent questions, tags, and pagination |
| \`question-detail.png\` | 1280x800 | Question content, Markdown rendering, answers, and vote scores |
| \`ask-question.png\` | 1280x800 | New question form with title, body, and tag selection |
| \`moderation-queue.png\` | 1280x800 | Moderator view with topic and answer actions |
| \`admin-users.png\` | 1280x800 | Administrator user list and role controls |
| \`search-results.png\` | 1280x800 | Search results with query and matching questions |
| \`mobile-question-list.png\` | 390px wide | Responsive question list on a mobile viewport |

Use browser zoom at 100 percent. Capture the actual running application with realistic local data. Avoid browser chrome, personal information, credentials, and development tools in the image.

## Social preview

Create a 1280x640 social preview image using the question-list screen as its source. Keep the CoreQA identity and interface visible without adding unverified product claims.

## README screenshot section

After the images exist and have been checked in the repository, this section can be added to the main README:

\`\`\`markdown
## Screenshots

![Question list](doc/images/question-list.png)
![Question detail](doc/images/question-detail.png)
![Ask a question](doc/images/ask-question.png)
![Moderation queue](doc/images/moderation-queue.png)
![Administrator users](doc/images/admin-users.png)
![Search results](doc/images/search-results.png)
![Mobile question list](doc/images/mobile-question-list.png)
\`\`\`
