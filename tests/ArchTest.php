<?php

declare(strict_types=1);

if ((new ReflectionFunction('arch'))->getNumberOfRequiredParameters() === 0) {
    arch()->preset()->php();

    arch()->preset()->security();

    arch('it will not use dd(), ddd(), env(), or exit()')
        ->expect(['dd', 'ddd', 'env', 'exit'])
        ->each->not->toBeUsed();

    arch('the package source declares strict types')
        ->expect('Madgeek\Msg91')
        ->toUseStrictTypes();
} else {
    it('does not use dd(), ddd(), env(), or exit()', function () {
        foreach (packageSourceFiles() as $file) {
            expect(file_get_contents($file))->not->toMatch('/\b(?:dd|ddd|env|exit)\s*\(/');
        }
    });

    it('declares strict types in package source', function () {
        foreach (packageSourceFiles() as $file) {
            expect(file_get_contents($file))->toContain('declare(strict_types=1);');
        }
    });
}

/**
 * @return list<string>
 */
function packageSourceFiles(): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(dirname(__DIR__).'/src'),
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}
