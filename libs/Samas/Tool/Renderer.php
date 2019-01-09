<?php
namespace Samas\PHP7\Tool;

use \RuntimeException;
use \Psr\Http\Message\ResponseInterface;
use \Samas\PHP7\Kit\{AppKit, ArrayKit, HtmlKit, StrKit};

class Renderer
{
    const TYPE_PAGE  = 'page';
    const TYPE_BLOCK = 'block';

    private $type;
    private $response;
    private $layout  = '';
    private $title   = '';
    private $include = ['top' => [], 'bottom' => []];
    private $context = ['top' => [], 'bottom' => []];
    private $js_map  = ['top' => [], 'bottom' => []];
    private $css_map = [];
    private $params  = [];

    public function __construct(ResponseInterface $response, string $type, array $options = [])
    {
        $this->response = $response;
        $this->type     = $type;
        if (isset($options['include'])) {
            if (isset($options['include']['top']) || isset($options['include']['bottom'])) {
                if (isset($options['include']['top']) && is_array($options['include']['top'])) {
                    $this->include['top'] = $options['include']['top'];
                }
                if (isset($options['include']['bottom']) && is_array($options['include']['bottom'])) {
                    $this->include['bottom'] = $options['include']['bottom'];
                }
            } elseif (is_array($options['include'])) {
                $this->include['bottom'] = $options['include'];
            }
        }
        if (isset($options['context'])) {
            if (isset($options['context']['top']) || isset($options['context']['bottom'])) {
                if (isset($options['context']['top']) && is_array($options['context']['top'])) {
                    $this->context['top'] = $options['context']['top'];
                }
                if (isset($options['context']['bottom']) && is_array($options['context']['bottom'])) {
                    $this->context['bottom'] = $options['context']['bottom'];
                }
            } elseif (is_array($options['context'])) {
                $this->context['bottom'] = $options['context'];
            }
        }
        if (isset($options['js'])) {
            if (isset($options['js']['top']) || isset($options['js']['bottom'])) {
                if (isset($options['js']['top']) && is_array($options['js']['top'])) {
                    $this->js_map['top'] = $options['js']['top'];
                }
                if (isset($options['js']['bottom']) && is_array($options['js']['bottom'])) {
                    $this->js_map['bottom'] = $options['js']['bottom'];
                }
            } elseif (is_array($options['js'])) {
                $this->js_map['bottom'] = $options['js'];
            }
        }
        if (isset($options['css']) && is_array($options['css'])) {
            $this->css_map = $options['css'];
        }
        if (isset($options['layout'])) {
            $this->layout = $options['layout'];
        }
    }

    /**
     * get render settings
     * @return array
     */
    public function getConfig(): array
    {
        return get_object_vars($this);
    }

    /**
     * set layout
     * @param  string  $layout   layout config
     * @return Renderer
     */
    public function setLayout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * unset layout
     * @return Renderer
     */
    public function unsetLayout(): self
    {
        $this->layout = '';
        return $this;
    }

    /**
     * set title
     * @param  string  $title   title
     * @return Renderer
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * unset title
     * @return Renderer
     */
    public function unsetTitle(): self
    {
        $this->title = '';
        return $this;
    }

    /**
     * set render variables
     * pattern1:
     * @param  string  $key    variables key
     * @param  mixed   $value  variables value
     * pattern2:
     * @param  array   $var_array  key-value of variables
     * @return Renderer
     */
    public function setVar($key, $value): self
    {
        if (is_array($key)) {
            $this->params = array_merge($this->params, $key);
        } else {
            $this->params[$key] = $value;
        }
        return $this;
    }

    /**
     * clear render variables
     * @param  string  $key  variables key, '' means clear all variables
     * @return Renderer
     */
    public function unsetVar(string $key = ''): self
    {
        if ($key === '') {
            $this->params = [];
        } elseif (array_key_exists($key, $this->params)) {
            unset($this->params[$key]);
        }
        return $this;
    }

    /**
     * register include file
     * @param  string  $alias     register alias
     * @param  string  $path      include file path
     * @param  string  $position  ['top' | 'bottom'] specific position, default is bottom
     * @return Renderer
     */
    public function setInclude(string $alias, string $path, string $position = 'bottom'): self
    {
        $position = in_array($position, array_keys($this->include)) ? $position : 'bottom';
        $this->include[$position][$alias] = $path;
        return $this;
    }

    /**
     * unregister include file
     * @param  string  $alias     register alias
     * @param  string  $position  ['top' | 'bottom'] specific position, empty string means match all position
     * @return Renderer
     */
    public function unsetInclude(string $alias = '', string $position = ''): self
    {
        $target_list = in_array($position, array_keys($this->include)) ? [$position] : array_keys($this->include);
        foreach ($target_list as $target) {
            if ($alias === '') {
                $this->include[$target] = [];
            } elseif (array_key_exists($alias, $this->include[$target])) {
                unset($this->include[$target][$alias]);
            }
        }
        return $this;
    }

    /**
     * register context
     * @param  string  $alias     register alias
     * @param  string  $content   context content
     * @param  string  $position  ['top' | 'bottom'] specific position, default is bottom
     * @return Renderer
     */
    public function setContext(string $alias, string $content, string $position = 'bottom'): self
    {
        $position = in_array($position, array_keys($this->context)) ? $position : 'bottom';
        $this->context[$position][$alias] = $content;
        return $this;
    }

    /**
     * unregister context
     * @param  string  $alias     register alias
     * @param  string  $position  ['top' | 'bottom'] specific position, empty string means match all position
     * @return Renderer
     */
    public function unsetContext(string $alias = '', string $position = ''): self
    {
        $target_list = in_array($position, array_keys($this->context)) ? [$position] : array_keys($this->context);
        foreach ($target_list as $target) {
            if ($alias === '') {
                $this->context[$target] = [];
            } elseif (array_key_exists($alias, $this->context[$target])) {
                unset($this->context[$target][$alias]);
            }
        }
        return $this;
    }

    /**
     * register js file
     * @param  string  $alias     register alias
     * @param  string  $path      js file path
     * @param  string  $position  ['top' | 'bottom'] specific position, default is bottom
     * @return Renderer
     */
    public function setJS(string $alias, string $path, string $position = 'bottom'): self
    {
        $position = in_array($position, array_keys($this->js_map)) ? $position : 'bottom';
        $this->js_map[$position][$alias] = $path;
        return $this;
    }

    /**
     * unregister js file
     * @param  string  $alias     register alias
     * @param  string  $position  ['top' | 'bottom'] specific position, empty string means match all position
     * @return Renderer
     */
    public function unsetJS(string $alias = '', string $position = ''): self
    {
        $target_list = in_array($position, array_keys($this->js_map)) ? [$position] : array_keys($this->js_map);
        foreach ($target_list as $target) {
            if ($alias === '') {
                $this->js_map[$target] = [];
            } elseif (array_key_exists($alias, $this->js_map[$target])) {
                unset($this->js_map[$target][$alias]);
            }
        }
        return $this;
    }

    /**
     * register css file
     * @param  string  $alias  register alias
     * @param  string  $path   css file path
     * @return Renderer
     */
    public function setCSS(string $alias, string $path): self
    {
        $this->css_map[$alias] = $path;
        return $this;
    }

    /**
     * unregister css file
     * @param  string  $alias  register alias, '' means clear all css files
     * @return Renderer
     */
    public function unsetCSS(string $alias = ''): self
    {
        if ($alias === '') {
            $this->css_map = [];
        } elseif (array_key_exists($alias, $this->css_map)) {
            unset($this->css_map[$alias]);
        }
        return $this;
    }

    /**
     * render by type
     * @param  string  $render_file  html file path
     * @param  bool    $return       return html instead of render
     * @return mixed
     */
    public function render(string $render_file, bool $return = false)
    {
        extract($this->params);
        ob_start();
        include $render_file;
        $main_content = ob_get_contents();
        ob_end_clean();
        $head = $this->getHeadContent();
        $foot = $this->getFootContent();
        $content = $this->getPrependContent() . $main_content . $this->getAppendContent();
        if ($this->type == self::TYPE_PAGE) {
            $content = $this->applyLayout($content, $head, $foot);
        } else {
            $title = $this->title !== '' ? "<title>{$this->title}</title" : '';
            $content = $title . $head . $content . $foot;
        }
        if ($return) {
            return $content;
        }
        $this->response->getBody()->write($content);
        return $this->response;
    }

    /**
     * compose full html by layout
     * @param  string  $content
     * @param  string  $head
     * @param  string  $foot
     * @return string
     */
    private function applyLayout(string $content, string $head = '', string $foot = ''): string
    {
        if (!empty($this->layout) && file_exists($this->layout)) {
            $layout_content = file_get_contents($this->layout);
            if (strpos($layout_content, '$content') === false) {
                throw new RuntimeException("layout file need contains '\$content'");
            }
            if ($this->title !== '') {
                $title = StrKit::output($this->title);
                if (strpos($layout_content, '<title>') === false) {
                    $head .= "\n\t<title>{$title}</title>\n";
                } else {
                    $head .= "\n\t" . HtmlKit::script([], "document.title=('{$title}');", true) . "\n";
                }
            }
            if (strpos($layout_content, '$head') === false) {
                $content = $head . $content;
            }
            if (strpos($layout_content, '$foot') === false) {
                $content .= $foot;
            }
            extract($this->params);
            ob_start();
            include $this->layout;
            $result = ob_get_contents();
            ob_end_clean();
            return $result;
        } else {
            $title = $this->title !== '' ?
                     HtmlKit::script([], "document.title=('" . StrKit::output($this->title) . "');") :
                     '';
            return "<!DOCTYPE html><head>{$head}{$title}</head><body>{$content}{$foot}</body></html>";
        }
    }

    /**
     * head string of js links and css links
     * @return string
     */
    private function getHeadContent(): string
    {
        $head = '';
        $ts = AppKit::config('static_ts') ? '?' . AppKit::config('static_ts') : '';
        foreach ($this->css_map as $css) {
            if (!empty($css)) {
                $suffix = substr($css, 0, 4) == 'http' ? '' : $ts;
                $head .= "\t<link type=\"text/css\" rel=\"stylesheet\" href=\"{$css}{$suffix}\">" .
                            "</link>\n";
            }
        }
        foreach ($this->js_map['top'] as $js) {
            if (!empty($js)) {
                $suffix = substr($js, 0, 4) == 'http' ? '' : $ts;
                $head .= "\t<script type=\"text/javascript\" src=\"{$js}{$suffix}\"></script>\n";
            }
        }
        return $head;
    }

    /**
     * prepend string of include files or context
     * @return string
     */
    private function getPrependContent(): string
    {
        $prepend = '';
        foreach ($this->include['top'] as $file) {
            if (!empty($file) && file_exists($file)) {
                ob_start();
                include $file;
                $prepend .= ob_get_contents();
                ob_end_clean();
            }
        }
        foreach ($this->context['top'] as $string) {
            if (!empty($string)) {
                $prepend .= $string;
            }
        }
        return $prepend;
    }

    /**
     * append string of include files or context
     * @return string
     */
    private function getAppendContent(): string
    {
        $append = '';
        foreach ($this->context['bottom'] as $string) {
            if (!empty($string)) {
                $append .= $string;
            }
        }
        foreach ($this->include['bottom'] as $file) {
            if (!empty($file) && file_exists($file)) {
                ob_start();
                include $file;
                $append .= ob_get_contents();
                ob_end_clean();
            }
        }
        return $append;
    }

    /**
     * foot string of js links behind content
     * @return string
     */
    private function getFootContent(): string
    {
        $foot = '';
        foreach ($this->js_map['bottom'] as $js) {
            if (!empty($js)) {
                $suffix = substr($js, 0, 4) == 'http' ? '' : $ts;
                $foot .= "\t<script type=\"text/javascript\" src=\"{$js}{$suffix}\"></script>\n";
            }
        }
        return $foot;
    }
}
