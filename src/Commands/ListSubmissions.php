<?php

namespace App\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class ListSubmissions extends Base
{
    protected static $defaultName = 'list-submissions';

    protected function configure()
    {
        $this->setDescription('List the students submissions of the course')
            ->setHelp('This command allows you to list the students submissions of your Google Classroom course.')
            ->addArgument('courseId', InputArgument::REQUIRED, 'Google Classroom course ID.')
            ->addArgument('courseWorkId', InputArgument::REQUIRED, 'Google Classroom course work ID.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Course information
        if (!$course = $this->googleClassroomService->getCourse($output, $input->getArgument('courseId'))) {
            return 0;
        }

        $output->writeln("<info>Course:</info> {$course->name}");

        // Assignment information
        if (!$assignment = $this->googleClassroomService->getAssignment($output, $input->getArgument('courseId'), $input->getArgument('courseWorkId'))) {
            return 0;
        }

        $output->write("<info>Assignment:</info> ");
        $output->write(("<fg=blue>{$assignment->getTitle()}</fg=blue> ({$assignment->getState()})"));
        if ($assignment->getDueDate()) {
            $output->write((" | Due: {$assignment->getDueDate()->getDay()}/{$assignment->getDueDate()->getMonth()}/{$assignment->getDueDate()->getYear()}"));
        }
        $output->write(" | Type : {$assignment->getWorkType()}");
        $output->writeln('');

        // Submissions
        if (!$results = $this->googleClassroomService->listSubmissions($output, $input->getArgument('courseId'), $input->getArgument('courseWorkId'))) {
            return 0;
        }

        $output->writeLn("<info>Submissions:</info> ");

        foreach ($results->getStudentSubmissions() as $submission) {
            // Student information
            if (!$student = $this->googleClassroomService->getStudent($output, $input->getArgument('courseId'), $submission->getUserId())) {
                return 0;
            }

            $output->write("<fg=cyan>{$student->getProfile()->name->fullName}</>");
            if ($submission->late) {
                $output->write(' (late)');
            }
            $output->writeln('');

            // Submission
            if ($submission->courseWorkType == "SHORT_ANSWER_QUESTION") {
                $output->writeLn($submission->shortAnswerSubmission->answer);
            } elseif ($submission->courseWorkType == "ASSIGNMENT") {
                foreach ($submission->assignmentSubmission as $attachment) {
                    // Link
                    if (!empty($attachment->link)) {
                        $output->writeLn("Link: {$attachment->link->url}");
                    }
                    // Drive file
                    if (!empty($attachment->driveFile)) {
                        $output->writeLn("Drive file: {$attachment->driveFile->title} - {$attachment->driveFile->alternateLink}");
                    }
                    // Youtube video
                    if (!empty($attachment->youTubeVideo)) {
                        $output->writeLn("Youtube video: {$attachment->youTubeVideo->title} - {$attachment->youTubeVideo->alternateLink}");
                    }
                    // Form
                    if (!empty($attachment->form)) {
                        $output->writeLn("Form: {$attachment->form->title} - {$attachment->form->formUrl} / {$attachment->form->responseUrl}");
                    }
                }
            }
        }

        return 0;
    }
}
