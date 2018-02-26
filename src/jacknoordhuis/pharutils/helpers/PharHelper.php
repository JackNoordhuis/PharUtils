<?php

declare(strict_types=1);

namespace jacknoordhuis\pharutils\helpers;

use jacknoordhuis\pharutils\Utils;
use Symfony\Component\Console\Output\OutputInterface;

class PharHelper {

	/**
	 * @param string               $pharPath
	 * @param string               $basePath
	 * @param array                $includedPaths
	 * @param array                $excludedSubstrings
	 * @param array                $metadata
	 * @param string               $stub
	 * @param int                  $signatureAlgo
	 * @param OutputInterface|null $output
	 */
	public static function buildPhar(string $pharPath, string $basePath, array $includedPaths, array $excludedSubstrings, array $metadata, string $stub, $output = null, int $signatureAlgo = \Phar::SHA1) {
		if(file_exists($pharPath)) {
			$output->writeln("<comment>Phar file already exists, overwriting...</comment>");
			\Phar::unlinkArchive($pharPath);
		}

		$output->writeln("<info>Adding files...</info>");

		$start = microtime(true);
		$phar = new \Phar($pharPath);
		$phar->setMetadata($metadata);
		$phar->setStub($stub);
		$phar->setSignatureAlgorithm($signatureAlgo);
		$phar->startBuffering();

		if(empty($excludedSubstrings)) { // If not excluded substrings were provided
			$excludedSubstrings = [
				"/.", // "Hidden" files, git information etc
				realpath($pharPath), // don't add the phar to itself
			];
		}

		$regex = sprintf("/^(?!.*(%s))^%s(%s).*/i",
			implode("|", Utils::preg_quote_array($excludedSubstrings, "/")), // String may not contain any of these substrings
			preg_quote($basePath, "/"), // String must start with this path...
			implode("|", Utils::preg_quote_array($includedPaths, "/")) // ...and must be followed by one of these relative paths, if any were specified. If none, this will produce a null capturing group which will allow anything.
		);

		$directory = new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS | \FileSystemIterator::CURRENT_AS_PATHNAME); // can't use fileinfo because of symlinks
		$iterator = new \RecursiveIteratorIterator($directory);
		$regexIterator = new \RegexIterator($iterator, $regex);

		$count = count($phar->buildFromIterator($regexIterator, $basePath));
		$output->writeln("<info>Added $count files</info>");

		$output->writeln("<info>Checking for compressible files...</info>");
		foreach($phar as $file => $finfo) {
			/** @var \PharFileInfo $finfo */
			if($finfo->getSize() > (1024 * 512)) {
				$output->writeln("<fg=orange>Compressing " . $finfo->getFilename() . "</>");
				$finfo->compress(\Phar::GZ);
			}
		}

		$phar->stopBuffering();

		$output->writeln("<fg=yellow;options=bold>Done in " . round(microtime(true) - $start, 3) . "s</>");
	}

}