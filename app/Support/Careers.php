<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * The Developer Careers page's open roles. Edit them here: each needs a
 * unique slug, which applications are filed under. Remove a role to close
 * it; applications already made for it keep their role's name.
 */
class Careers
{
    /**
     * The teams roles belong to, for the page's filters.
     */
    public const TEAMS = [
        'engineering' => 'Engineering',
        'games' => 'Kiosk games',
        'ai' => 'AI',
        'field' => 'Kiosk deployment',
    ];

    /**
     * Applying without a particular role in mind.
     */
    public const OPEN_APPLICATION = 'open-application';

    /**
     * @return Collection<string, array{slug: string, title: string, team: string, type: string, level: string, location: string, summary: string, you_will: list<string>, you_bring: list<string>, skills: list<string>}>
     */
    public static function roles(): Collection
    {
        return collect([
            [
                'slug' => 'laravel-developer',
                'title' => 'Laravel Developer',
                'team' => 'engineering',
                'type' => 'Full-time',
                'level' => 'Mid / Senior',
                'location' => 'Remote-first',
                'summary' => 'Build the platform behind our kiosks: client projects, Kiosk Studio’s back end, game templates and Xelenic Cloud.',
                'you_will' => [
                    'Build features across the client panel, the admin panel and the APIs Kiosk Studio talks to',
                    'Integrate third-party services, such as the cloud provider behind Xelenic Cloud',
                    'Write tests for what you ship, and review your teammates’ code',
                    'Help shape how the platform grows as more kiosks come online',
                ],
                'you_bring' => [
                    'Solid PHP and Laravel experience, with Eloquent, queues and testing',
                    'Comfort with SQL databases and designing clean APIs',
                    'Care for security and for code other people can read',
                    'Good written English for working with a distributed team',
                ],
                'skills' => ['PHP', 'Laravel', 'MySQL', 'REST APIs', 'Pest'],
            ],
            [
                'slug' => 'frontend-javascript-developer',
                'title' => 'Frontend / JavaScript Developer',
                'team' => 'engineering',
                'type' => 'Full-time',
                'level' => 'Mid',
                'location' => 'Remote-first',
                'summary' => 'Work on Kiosk Studio, the browser-based editor our clients design their kiosk apps in, and the player that runs them.',
                'you_will' => [
                    'Build editor features: the flow map, the screen editor, the animation timeline',
                    'Keep the kiosk player fast and smooth on touchscreen hardware',
                    'Design interfaces with Tailwind and plain, dependency-light JavaScript',
                    'Test your work on real kiosks with the deployment team',
                ],
                'you_bring' => [
                    'Strong JavaScript, HTML and CSS, with an eye for detail',
                    'Experience building rich, interactive interfaces',
                    'An understanding of browser performance and accessibility',
                    'Curiosity about animation and touch interaction',
                ],
                'skills' => ['JavaScript', 'CSS', 'Tailwind', 'SVG', 'Canvas'],
            ],
            [
                'slug' => 'kiosk-game-developer',
                'title' => 'Kiosk Game Developer',
                'team' => 'games',
                'type' => 'Full-time',
                'level' => 'Mid',
                'location' => 'Remote-first',
                'summary' => 'Create the branded games that pull crowds to our kiosks: spin wheels, runners, memory games, scratch cards and whatever comes next.',
                'you_will' => [
                    'Design and build new HTML5 games for Kiosk Studio’s marketplace',
                    'Make games feel great on big portrait touchscreens',
                    'Add the settings that let clients brand each game as their own',
                    'Play-test with real people and tune the fun',
                ],
                'you_bring' => [
                    'Experience making games with Canvas, WebGL or a JavaScript game engine',
                    'A feel for game design: pacing, feedback and difficulty',
                    'Clean, efficient code that runs well on modest hardware',
                    'A portfolio of games you’ve built (jams count!)',
                ],
                'skills' => ['JavaScript', 'HTML5 Canvas', 'Game design', 'Animation'],
            ],
            [
                'slug' => 'ai-integration-engineer',
                'title' => 'AI Integration Engineer',
                'team' => 'ai',
                'type' => 'Full-time',
                'level' => 'Mid / Senior',
                'location' => 'Remote-first',
                'summary' => 'Build our AI middleware: the layer that connects AI models, APIs and data to our clients’ businesses and kiosks.',
                'you_will' => [
                    'Integrate language and vision models into client systems and kiosk apps',
                    'Design reliable pipelines for prompts, data and evaluations',
                    'Build tools that let non-engineers put AI to work safely',
                    'Measure what works, and explain it to clients',
                ],
                'you_bring' => [
                    'Hands-on experience building with AI model APIs',
                    'Strong backend skills in PHP, Python or Node.js',
                    'Good judgement about data privacy and model limitations',
                    'Clear communication with technical and non-technical people',
                ],
                'skills' => ['LLM APIs', 'Python', 'PHP', 'Prompt design', 'Evaluation'],
            ],
            [
                'slug' => 'kiosk-deployment-engineer',
                'title' => 'Kiosk Deployment Engineer',
                'team' => 'field',
                'type' => 'Full-time',
                'level' => 'Junior / Mid',
                'location' => 'On site and remote',
                'summary' => 'Get kiosks running in the real world: set up the hardware, install the apps, and keep them healthy at malls, events and stores.',
                'you_will' => [
                    'Install and configure kiosk hardware, screens and peripherals',
                    'Deploy kiosk apps and test them on site with clients',
                    'Diagnose and fix problems quickly, on site or remotely',
                    'Feed what you learn in the field back to the product team',
                ],
                'you_bring' => [
                    'Hands-on experience with computer hardware and networking',
                    'Familiarity with Windows or Linux and the command line',
                    'Calm problem solving when something breaks in public',
                    'A driving licence and readiness to travel to client sites',
                ],
                'skills' => ['Hardware', 'Networking', 'Windows', 'Linux', 'Troubleshooting'],
            ],
            [
                'slug' => 'software-engineering-intern',
                'title' => 'Software Engineering Intern',
                'team' => 'engineering',
                'type' => 'Internship',
                'level' => 'Student / Graduate',
                'location' => 'Remote-first',
                'summary' => 'Learn by shipping: join a product team and build real features for kiosks, Kiosk Studio and the cloud platform, with a mentor beside you.',
                'you_will' => [
                    'Work on real features with a mentor reviewing your code',
                    'Learn Laravel, modern JavaScript and how kiosks work end to end',
                    'Take part in planning, reviews and demos with the team',
                    'Finish with work you can show in your portfolio',
                ],
                'you_bring' => [
                    'Studying computer science or a related field, or recently graduated',
                    'Some projects of your own, in any language',
                    'Eagerness to learn and to ask questions',
                    'Good written English',
                ],
                'skills' => ['Programming basics', 'Git', 'Curiosity'],
            ],
        ])->keyBy('slug');
    }

    /**
     * Role choices for the application form: slug => title.
     *
     * @return array<string, string>
     */
    public static function choices(): array
    {
        return self::roles()->map(fn (array $role) => $role['title'])->all()
            + [self::OPEN_APPLICATION => 'Open application (any role)'];
    }

    /**
     * A role's title, by slug.
     */
    public static function title(string $slug): string
    {
        return self::choices()[$slug] ?? $slug;
    }
}
