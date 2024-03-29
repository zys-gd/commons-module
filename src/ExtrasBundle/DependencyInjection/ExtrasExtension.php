<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 13.01.2019
 * Time: 21:12
 */

namespace ExtrasBundle\DependencyInjection;


use ExtrasBundle\Config\DefinitionReplacer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class ExtrasExtension extends ConfigurableExtension implements PrependExtensionInterface
{

    /**
     * Configures the passed container according to the merged configuration.
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('parameters.yml');
        $loader->load('listeners.yml');
        $loader->load('services.yml');
        $loader->load('cache.yml');
        $loader->load('twig.yml');
        $loader->load('session.yml');

        $env = $container->getParameter('kernel.environment');

        if ($env == 'test') {
            $loader->load('testing.yml');
        }


        $definition = $container->getDefinition('ExtrasBundle\SignatureCheck\ParametersProvider');

        $definition->setArgument(0, $mergedConfig['signature_check']['request_parameter']);
        $definition->setArgument(1, $mergedConfig['signature_check']['secret_key']);


        if (isset($mergedConfig['cache']['use_array_cache']) && $mergedConfig['cache']['use_array_cache']) {
            $loader->load('redis-dummy.yml');
        }

        $redisProviderDefinition = $container->getDefinition('app.cache.redis_connection_provider');

        DefinitionReplacer::replacePlaceholder($redisProviderDefinition, $mergedConfig['cache']['redis_host'], '_redis_host_placeholder_');
        DefinitionReplacer::replacePlaceholder($redisProviderDefinition, $mergedConfig['cache']['redis_port'], '_redis_port_placeholder_');
        DefinitionReplacer::replacePlaceholder($redisProviderDefinition, $mergedConfig['cache']['redis_prefix'], '_redis_prefix_placeholder_');

        $definition = $container->getDefinition('ExtrasBundle\Command\GenerateAppVersionHashCommand');
        $definition->addMethodCall('setDestination', [$mergedConfig['app_hash_file_path']]);


    }

    /**
     * Allow an extension to prepend the extension configurations.
     */
    public function prepend(ContainerBuilder $container)
    {

        $webProfilerOverride  = realpath(__DIR__ . '/../Resources/WebProfilerBundle/views');
        $guzzleBundleOverride = realpath(__DIR__ . '/../Resources/EmoeGuzzleBundle/views');


        $container->loadFromExtension('twig', array(
            'paths' => array(
                $webProfilerOverride  => 'WebProfiler',
                $guzzleBundleOverride => 'EmoeGuzzle',
            ),
        ));

    }
}